<?php

namespace Acquia\Cli\CloudApi;

use Acquia\Cli\ConnectorFactoryInterface;
use AcquiaCloudApi\Connector\Connector;
use League\OAuth2\Client\Token\AccessToken;

class ConnectorFactory implements ConnectorFactoryInterface {

  protected array $config;
  protected ?string $baseUri;

  /**
   * ConnectorFactory constructor.
   *
   * @param array $config
   * @param string|null $base_uri
   */
  public function __construct(array $config, string $base_uri = NULL) {
    $this->config = $config;
    $this->baseUri = $base_uri;
  }

  /**
   * @return \Acquia\Cli\CloudApi\AccessTokenConnector|\AcquiaCloudApi\Connector\Connector
   */
  public function createConnector(): Connector|AccessTokenConnector {
    // A defined key & secret takes priority.
    if ($this->config['key'] && $this->config['secret']) {
      return new Connector($this->config, $this->baseUri);
    }

    // Fall back to a valid access token.
    if ($this->config['accessToken']) {
      $access_token = $this->createAccessToken();
      if (!$access_token->hasExpired()) {
        // @todo Add debug log entry indicating that access token is being used.
        return new AccessTokenConnector([
          'access_token' => $access_token,
          'key' => NULL,
          'secret' => NULL,
        ], $this->baseUri);
      }
    }

    // Fall back to an unauthenticated request.
    return new Connector($this->config, $this->baseUri);
  }

  /**
   * @return \League\OAuth2\Client\Token\AccessToken
   */
  private function createAccessToken(): AccessToken {
    return new AccessToken([
      'access_token' => $this->config['accessToken'],
      'expires' => $this->config['accessTokenExpiry'],
    ]);
  }

}
