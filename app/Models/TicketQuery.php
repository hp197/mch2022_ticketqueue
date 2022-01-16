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

  protected function _getJsonData($url)
  {
    $cache = \Config\Services::cache();

    if (($cache_data = $cache->get(md5($url))) !== false)
    {
      return $cache_data['results'];
    }

    $_data = [
      'count' => 0,
      'next'  => $url,
      'previous' => null,
      'results' => [],
    ];

    $client = \Config\Services::curlrequest();
    $this->req_headers['headers']['Authorization'] = 'Token ' . getenv('ticketshop_apitoken');

    while (strlen($_data['next']) > 0)
    {
      $response = $client->request('GET', $url, $this->req_headers);
      $data = json_decode($response->getBody(), true);

      $_data['count'] += $data['count'];
      $_data['next'] = $data['next'];
      $_data['previous'] = $data['previous'];
      $_data['results'] = array_merge($_data['results'], $data['results']);
    }

    $cache->save(md5($url), $data, 60);
    return $data['results'];
  }
}

