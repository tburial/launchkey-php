<?php

namespace LaunchKey\Http;

use Guzzle\Http\Client as Guzzle;
use Guzzle\Http\Exception\RequestException;
use LaunchKey\Exception\ApiException;
use LaunchKey\LaunchKey;

/**
 * @internal
 */
class Client {

    private $client;

    private $adapter;

    public function __construct($client, $adapter = NULL)
    {
        $this->client  = $client;

        if ($adapter === NULL)
        {
            $adapter = $this->default_adapter();
        }

        $this->adapter = $adapter;
    }

    public function get($path = NULL, $params = array(), $headers = array())
    {
        $request = $this->adapter->get($path, $headers);
        $query   = $request->getQuery();

        foreach ($params as $param => $value)
        {
            $query->set($param, $value);
        }

        try
        {
            return $request->send()->json();
        }
        catch (RequestException $e)
        {
            throw new ApiException($e);
        }
    }

    public function post($path = NULL, $body = NULL, $headers = array())
    {
        $request = $this->adapter->post($path, $headers);
        $request->addPostFields($body);

        try
        {
            return $request->send()->json();
        }
        catch (RequestException $e)
        {
            throw new ApiException($e);
        }
    }

    public function put($path = NULL, $body = NULL, $headers = array())
    {
        $request = $this->adapter->put($path, $headers);
        $request->addPostFields($body);

        try
        {
            return $request->send()->json();
        }
        catch (RequestException $e)
        {
            throw new ApiException($e);
        }
    }

    public function user_agent()
    {
        $os_info = php_uname('m').'-'.strtolower(php_uname('s')).php_uname('r');

        return sprintf(
            'launchkey-php/%s (Composer; PHP %s %s)',
            LaunchKey::VERSION, phpversion(), $os_info
        );
    }

    public function endpoint()
    {
        return 'https://'.$this->client['host'].'/'.LaunchKey::API_VERSION.'/';
    }

    public function default_adapter()
    {
        $options = array(
            'timeout'                   => $this->client['http_read_timeout'],
            'connect_timeout'           => $this->client['http_open_timeout'],
            'ssl.certificate_authority' => $this->client['certificate_authority'],
        );

        $adapter = new Guzzle($this->endpoint(), array(), $options);

        $adapter->setUserAgent($this->user_agent());

        $adapter->addSubscriber(
            new SignedRequestPlugin($this->client)
        );

        return $adapter;
    }

} // End Client
