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

        $_count = $props->available ? ($props->paid_orders + $props->pending_orders) : $props->total_size;
        $quotas[$tickettype] = [
          'available'        => $props->available,
          'available_number' => $props->available_number,
          'quota'            => $props->total_size,
          'sold'             => $_count,
        ];
      }

      return $quotas;
    }

    protected function sendQuotas(Array $_data)
    {
      $cache = \Config\Services::cache();
      $mqtt = new \PhpMqtt\Client\MqttClient(env('mqtt_server'), env('mqtt_port'), env('mqtt_clientid'));

      $connectionSettings = (new \PhpMqtt\Client\ConnectionSettings)
        ->setUsername(env('mqtt_username'))
        ->setPassword(env('mqtt_password'))
        ->setConnectTimeout(30);

      $mqtt->connect($connectionSettings);

      if ($mqtt->isConnected())
      {
        foreach ($_data as $idx => $val)
        {
          if (($cache_data = $cache->get(md5($idx))) !== null)
          {
            if (isset($cache_data['sold']) && $cache_data['sold'] == $val['sold'])
            {
              continue;
            }
          }

          $_name = sprintf('mch2022/ticketshop/%s', str_replace(' ', '', $idx));
          $mqtt->publish($_name, json_encode($val), 0, true);
          $cache->save(md5($idx), $val, (60 * 60 * 24));
        }

        $mqtt->disconnect();
      }
    }
}
