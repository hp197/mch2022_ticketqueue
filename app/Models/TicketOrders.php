<?php

namespace App\Models;

class TicketOrders extends TicketQuery
{
  protected $_data = [];
  protected $_products = [];

  public function __construct()
  {
    $this->_products = (new TicketProducts())->__toArray();
    $this->_getTicketOrders();
  }

  protected function _getTicketOrders()
  {
    $url = 'https://tickets.ifcat.org/api/v1/organizers/ifcat/events/mch2022/orders/';
    $_data = $this->_getJsonDataAll($url);

    foreach ($_data as $order)
    {
      foreach ($this->_getStripOrderKeys() as $key)
      {
        unset($order[$key]);
      }

      $this->_data[$order['code']] = $order;

      foreach ($this->_data[$order['code']]['positions'] as $idx => $position)
      {
        foreach ($this->_getStripPositionKeys() as $key)
        {
          unset($this->_data[$order['code']]['positions'][$idx][$key]);
        }

        $this->_data[$order['code']]['positions'][$idx]['order'] = &$this->_data[$order['code']];
        $this->_data[$order['code']]['positions'][$idx]['product'] = &$this->_products[$position['item']];
        $this->_products[$position['item']]['orders'][] = &$this->_data[$order['code']]['positions'][$idx];
      }
    }
  }

  protected function _getStripOrderKeys()
  {
    return ['secret', 'email', 'phone', 'payment_provider', 'fees', 'custom_followup_at', 'invoice_address', 'downloads', 'payments', 'refunds', 'require_approval', 'sales_channel', 'url', 'customer'];
  }

  protected function _getStripPositionKeys()
  {
    return ['attendee_name', 'attendee_name_parts', 'company', 'street', 'zipcode', 'city', 'country', 'state', 'attendee_email', 'tax_rate', 'tax_value', 'secret', 'addon_to', 'subevent', 'downloads', 'answers', 'tax_rule', 'pseudonymization_id', 'seat'];
  }

  public function getProducts(): array
  {
    return $this->_products;
  }

  public function getPositions($orderid): array
  {
    if (!$this->__isset($orderid))
    {
      return [];
    }

    return $this->_data[$orderid]['positions'];
  }

  public function __serialize(): array
  {
    return $this->_data;
  }

  public function __unserialize(array $data): void
  {
    $this->_data = $data;
  }

  public function __get(string $name)
  {
    if (array_key_exists($name, $this->_data)) {
      return $this->_data[$name];
    }

    $trace = debug_backtrace();
    trigger_error(
        'Undefined property via __get(): ' . $name .
        ' in ' . $trace[0]['file'] .
        ' on line ' . $trace[0]['line'],
        E_USER_NOTICE);
    return null;
  }

  public function __isset(string $name): bool
  {
    return isset($this->_data[$name]);
  }

  public function __toArray(): Array
  {
    return $this->_data;
  }
}
