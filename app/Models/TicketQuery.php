<?php

namespace App\Models;

abstract class TicketQuery
{
  protected $req_headers = [
    'allow_redirects' => true,
    'headers'         => [
      'Accept'        => 'application/json',
      'Authorization' => '',
    ],
    'user_agent'      => 'Tickershop fetcher (hp197)',
  ];

  protected function _getJsonDataAll($url)
  {
    $cache = \Config\Services::cache();

    if (($cache_data = $cache->get(md5($url))) !== null)
    {
      return $cache_data['results'];
    }

    $_data = [
      'count' => 0,
      'next'  => $url,
      'previous' => null,
      'results' => [],
    ];


    while (strlen($_data['next']) > 0)
    {
      $data = $this->_getJsonData($_data['next']);

      $_data['count'] += $data['count'];
      $_data['next'] = $data['next'];
      $_data['previous'] = $data['previous'];

      $_data['results'] = array_merge($_data['results'], $data['results']);
    }

    $cache->save(md5($url), $_data, 60);
    return $_data['results'];
  }

  protected function _getJsonData($url)
  {
    $client = \Config\Services::curlrequest();
    $_headers = $this->req_headers;
    $_headers['headers']['Authorization'] = 'Token ' . getenv('ticketshop_apitoken');

    $response = $client->request('GET', $url, $_headers);
    return json_decode($response->getBody(), true);
  }
}

