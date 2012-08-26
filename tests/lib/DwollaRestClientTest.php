<?php

class DwollaRestClientTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var DwollaRestClient
     */
    private $client;

    /**
     * @var array 
     */
    private $config;

    public function setUp()
    {
        $this->config = include __DIR__ . '/../config.php';
        $this->client = new DwollaRestClient(
                $this->config['apiKey'],
                $this->config['apiSecret'],
                $this->config['redirectUri'],
                $permissions = array("send", "transactions", "balance", "request", "contacts", "accountinfofull", "funding"),
                $mode = 'TEST'
        );
    }

    public function testObjectCreation()
    {
        $this->assertInstanceOf('DwollaRestClient', $this->client);
    }

    public function testGetAuthUrl()
    {
        $authUrl = $this->client->getAuthUrl();

        $components = parse_url($authUrl);
        $this->assertEquals('https', $components['scheme']);
        $this->assertEquals('www.dwolla.com', $components['host']);
        $this->assertEquals('/oauth/v2/authenticate', $components['path']);
        
        parse_str($components['query'], $query);
        $this->assertEquals($this->config['apiKey'], $query['client_id']);
        $this->assertEquals('code', $query['response_type']);
        $this->assertEquals('send|transactions|balance|request|contacts|accountinfofull|funding', $query['scope']);
        $this->assertEquals($this->config['redirectUri'], $query['redirect_uri']);
    }

}
