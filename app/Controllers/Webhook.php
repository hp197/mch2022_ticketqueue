<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;

class Webhook extends BaseController
{
    use ResponseTrait;

    public function neworder()
    {
      $quotas = $this->getQuotas();
      $this->sendQuotas($quotas);
      $products = $this->getProducts();
      $this->sendProducts($products);
      return $this->respond('ok', 200);
    }

    protected function getQuotas(): array
    {
      $ticketQuota = new \App\Models\TicketQuota();

      $quotas = [];
      $whitelisted = ['Camper Ticket', 'Early-Bird Tickets', 'Harbour over 12m', 'Harbour under 12m', 'Regular Tickets'];
      $_data = $ticketQuota->__toArray();

      foreach ($_data as $tickettype => $props)
      {
        if (!in_array($tickettype, $whitelisted))
        {
          continue;
        }

        $quotas[$tickettype] = [
          'available'        => $props['available'],
          'available_number' => $props['available_number'],
          'quota'            => $props['total_size'],
          'sold'             => $props['paid_orders'] + $props['pending_orders'],
        ];
      }

      return $quotas;
    }

    protected function getProducts(): array
    {
      $ticketOrders = new \App\Models\TicketOrders();
      $whitelisted = [
        124, 126, 138, 140, 146, 107, 125, 127, 139, 108, 128, 142, 109, 129, 110, 130, 111, 131,
        112, 132, 113, 133, 114, 115, 144, 121, 122, 134, 123, 135, 136, 137
      ];
      $products = $ticketOrders->getProducts();
      $ouput = [];

      foreach ($whitelisted as $idx)
      {
        if (!isset($output[$products[$idx]['name']['en']]))
        {
          $output[$products[$idx]['name']['en']] = 0;
        }
        if (!isset($products[$idx]['orders']))
        {
          $products[$idx]['orders'] = [];
        }

        foreach (array_keys($products[$idx]['orders']) as $o_idx)
        {
          if (!in_array($products[$idx]['orders'][$o_idx]['order']['status'], ['p', 'n']))
          {
            unset($products[$idx]['orders'][$o_idx]);
          }
        }

        $output[$products[$idx]['name']['en']] += count($products[$idx]['orders']);
      }

      return $output;
    }

    protected function sendQuotas(Array $_data)
    {
      $cache = \Config\Services::cache();
      $mqtt = new \PhpMqtt\Client\MqttClient(getenv('mqtt_server'), getenv('mqtt_port'), getenv('mqtt_clientid'));

      $connectionSettings = (new \PhpMqtt\Client\ConnectionSettings)
        ->setUsername(getenv('mqtt_username'))
        ->setPassword(getenv('mqtt_password'))
        ->setConnectTimeout(30);

      $mqtt->connect($connectionSettings);

      if ($mqtt->isConnected())
      {
        foreach ($_data as $idx => $val)
        {
          if (($cache_data = $cache->get(md5('quota_' . $idx))) !== null)
          {
            if (isset($cache_data['sold']) && $cache_data['sold'] == $val['sold'])
            {
              continue;
            }
          }

          $_name = sprintf('mch2022/ticketshop/%s', str_replace(' ', '', $idx));
          $mqtt->publish($_name, json_encode($val), 0, true);
          $cache->save(md5('quota_' . $idx), $val, (60 * 60 * 24));
        }

        $mqtt->disconnect();
      }
    }

    protected function sendProducts(Array $_data)
    {
      $cache = \Config\Services::cache();
      $mqtt = new \PhpMqtt\Client\MqttClient(getenv('mqtt_server'), getenv('mqtt_port'), getenv('mqtt_clientid'));

      $connectionSettings = (new \PhpMqtt\Client\ConnectionSettings)
        ->setUsername(getenv('mqtt_username'))
        ->setPassword(getenv('mqtt_password'))
        ->setConnectTimeout(30);

      $mqtt->connect($connectionSettings);

      if ($mqtt->isConnected())
      {
        foreach ($_data as $idx => $val)
        {
          if (($cache_data = $cache->get(md5('products'))) !== null)
          {
            if (isset($cache_data[$idx]) && $cache_data[$idx] == $val)
            {
              continue;
            }
          }

          $_name = sprintf('mch2022/ticketshop/sold/%s', $idx);
          $mqtt->publish($_name, $val, 0, true);
        }

        $cache->save(md5('products'), $_data, (60 * 60 * 24));
        $mqtt->disconnect();
      }
    }
}
