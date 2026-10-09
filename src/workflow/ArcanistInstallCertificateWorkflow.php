<?php

/**
 * Installs arcanist certificates.
 */
final class ArcanistInstallCertificateWorkflow extends ArcanistWorkflow {

  public function getWorkflowName() {
    return 'install-certificate';
  }

  public function getCommandSynopses() {
    return phutil_console_format(<<<EOTEXT
      **install-certificate** [uri]
EOTEXT
      );
  }

  public function getCommandHelp() {
    return phutil_console_format(<<<EOTEXT
          Supports: http, https
          Installs Conduit credentials into your ~/.arcrc for the given server.
          You need to do this before you can use 'arc', as it enables 'arc' to
          link your command-line activity with your account on the web. Run
          this command from within a project directory to install that
          project's certificate, or specify an explicit URI (like
          "https://devtools.example.com/").
EOTEXT
      );
  }

  public function getArguments() {
    return array(
      '*' => 'uri',
    );
  }

  protected function shouldShellComplete() {
    return false;
  }

  public function requiresConduit() {
    return false;
  }

  public function requiresWorkingCopy() {
    return false;
  }

  public function run() {
    $console = PhutilConsole::getConsole();

    $uri = $this->determineConduitURI();
    $this->setConduitURI($uri);
    $configuration_manager = $this->getConfigurationManager();

    $config = $configuration_manager->readUserConfigurationFile();

    $this->writeInfo(
      pht('CONNECT'),
      pht(
        'Connecting to "%s"...',
        $uri));

    $conduit = $this->establishConduit()->getConduit();
    try {
      $conduit->callMethodSynchronous('conduit.ping', array());
    } catch (Throwable $ex) {
      throw new ArcanistUsageException(
        pht(
          'Failed to connect to server (%s): %s',
          $uri,
          $ex->getMessage()));
    }

    $token_uri = new PhutilURI($uri);
    $token_uri->setPath('/conduit/token/');

    $is_token_auth = false;
    try {
      $capabilities = $conduit->callMethodSynchronous(
        'conduit.getcapabilities',
        array());
      $auth = idx($capabilities, 'authentication', array());
      if (in_array('token', $auth)) {
        $token_uri->setPath('/conduit/login/');
        $is_token_auth = true;
      }
    } catch (Throwable $ex) {
      // Ignore.
    }

    if (!$is_token_auth) {
      throw new ArcanistUsageException(
        pht(
          'This %s server does not support modern login methods. '.
          'Seek out an %s client from the 2010s, or try updating the server.',
          PlatformSymbols::getPlatformServerName(),
          PlatformSymbols::getPlatformClientName()));
    }

    $console->writeOut("**%s**\n", pht('LOG IN'));
    $console->writeOut(
      "%s\n\n%s\n\n%s",
      pht(
        'Open this page in your browser and log in if necessary:'),
      $token_uri,
      pht('Then paste the API Token on that page below.'));

    do {
      $token = phutil_console_prompt(pht('Paste API Token from that page:'));
      $token = trim($token);
      if (strlen($token)) {
        break;
      }
    } while (true);

    if (strlen($token) != 32) {
      throw new ArcanistUsageException(
        pht(
          'The token "%s" is not formatted correctly. API tokens should '.
          'be 32 characters long. Make sure you visited the correct URI '.
          'and copy/pasted the token correctly.',
          $token));
    }

    if (strncmp($token, 'api-', 4) == 0) {
      $console->writeOut(
        pht(
          'You are installing a standard API token, but a CLI API token '.
          'was expected. If you\'re writing a script, consider passing the '.
          'token at runtime with --conduit-token instead of installing it.'));
      if (!phutil_console_confirm(pht('Install this token anyway?'))) {
        throw new ArcanistUsageException(pht('Not installing API token.'));
      }
    } else if (strncmp($token, 'cli-', 4) !== 0) {
      throw new ArcanistUsageException(
        pht(
          'The token "%s" is not formatted correctly. Valid API tokens '.
          'should begin "cli-" and be 32 characters long. Make sure you '.
          'visited the correct URI and copy/pasted the token correctly.',
          $token));
    }

    $conduit->setConduitToken($token);
    try {
      $conduit->callMethodSynchronous('user.whoami', array());
    } catch (Throwable $ex) {
      throw new ArcanistUsageException(
        pht(
          'The token "%s" is not a valid API Token. The server returned '.
          'this response when trying to use it as a token: %s',
          $token,
          $ex->getMessage()));
    }

    $config['hosts'][$uri] = array(
      'token' => $token,
    );

    echo pht('Writing %s...', '~/.arcrc')."\n";
    $configuration_manager->writeUserConfigurationFile($config);

    $console->writeOut(
      "<bg:green>** %s **</bg> %s\n",
      pht('SUCCESS!'),
      pht('API Token installed.'));

    return 0;
  }

  private function determineConduitURI() {
    $uri = $this->getArgument('uri');
    if (count($uri) > 1) {
      throw new ArcanistUsageException(pht('Specify at most one URI.'));
    } else if (count($uri) == 1) {
      $uri = reset($uri);
    } else {
      $conduit_uri = $this->getConduitURI();
      if (!$conduit_uri) {
        throw new ArcanistUsageException(
          pht(
            'Specify an explicit URI or run this command from within a '.
            'project which is configured with a %s.',
            '.arcconfig'));
      }
      $uri = $conduit_uri;
    }

    $example = 'https://devtools.example.com/';

    $uri_object = new PhutilURI($uri);
    $protocol = $uri_object->getProtocol();
    if (!$protocol || !$uri_object->getDomain()) {
      throw new ArcanistUsageException(
        pht(
          'Server URI "%s" must include a protocol and domain. It should be '.
          'in the form "%s".',
          $uri,
          $example));
    }

    $protocol = $uri_object->getProtocol();
    switch ($protocol) {
      case 'http':
      case 'https':
        break;
      default:
        throw new ArcanistUsageException(
          pht(
            'Server URI "%s" must include the "http" or "https" protocol. '.
            'It should be in the form "%s".',
            $uri,
            $example));
    }

    $uri_object->setPath('/api/');

    return (string)$uri_object;
  }

}
