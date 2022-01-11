<?php

namespace App\Models;

class TicketQuota
{
  protected $req_headers = [
    'allow_redirects' => true,
    'headers'         => [
      'Accept'        => 'application/json',
      'Authorization' => '',
    ],
    'user_agent'      => 'Tickershop fetcher (hp197)',
  ];

  protected $_data = [];

  public function __construct()
  {
    $this->_getTicketquotas();
  }

  protected function _getJsonData($url)
  {
    $client = \Config\Services::curlrequest();
    $this->req_headers['headers']['Authorization'] = 'Token ' . getenv('ticketshop_apitoken');
    $response = $client->request('GET', $url, $this->req_headers);

    return json_decode($response->getBody());
  }

  protected function _getTicketQuotas()
  {
    $url = 'https://tickets.ifcat.org/api/v1/organizers/ifcat/events/mch2022/quotas/';
    $_data = $this->_getJsonData($url);

    foreach ($_data->results as $quota)
    {
      $this->_data[$quota->name] = $this->_getTicketquota($quota->id);
    }
  }

  protected function _getTicketquota(int $id)
  {
    $url = sprintf('https://tickets.ifcat.org/api/v1/organizers/ifcat/events/mch2022/quotas/%d/availability/', $id);
    return $this->_getJsonData($url);
  }

  public function __serialize(): array
  {
    return $this->_data;
  }

  public function __unserialize(array $data): void
  {
    $this->_data = $data;
  }

  public function __get(string $name): mixed
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
