<?php

final class HTTPParserTestCase extends PhutilTestCase {

  public function testConfusingResponse() {

    $large_header = $this->getLargeHeader();

    $expected_body = <<<EOBODY
body starts here:


So this is part of the body, and can confuse the parser:

HTTP/2 100 CONTINUE

HTTP/2 304 OK
Header-one: fine
header-two: good


fin
EOBODY;

    $raw_response = <<<EORESPONSE
HTTP/1.1 100 CONTINUE

HTTP/1 200 Connection established

HTTP/2 100 CONTINUE
{$large_header}

HTTP/2 202
Header-one: fine
header-two: good

{$expected_body}
EORESPONSE;

    $this->parseResponseAndCheck($raw_response, 202, $expected_body);

    $raw_response = str_replace('\n', '\r\n', $raw_response);
    $expected_body = str_replace('\n', '\r\n', $expected_body);
    $this->parseResponseAndCheck($raw_response, 202, $expected_body);
  }

  private function parseResponseAndCheck(
    $raw_response,
    $expected_status,
    $expected_body) {

    $future = new HTTPSFuture('');

    /** @var HTTPFutureHTTPResponseStatus $status */
    list($status, $body, $headers) =
      $future->parseRawHTTPResponse($raw_response);

    $this->assertTrue(
      $status->getStatusCode() > 99,
      pht('Failed to parse message: %s', $status));

    $this->assertEqual(
      $expected_status,
      $status->getStatusCode(),
      pht('extracted wrong part of the response.'));

    $this->assertEqual($expected_body, $body);
  }

  private function getLargeHeader() {
    return 'ExtraLongHeader: '.str_repeat('abc', 50000);
  }

  public function testEmptyBody() {
    $raw_response = array(
      'HTTP/1.1 100 CONTINUE',
      '',
      'HTTP/1 201',
      'content-length: 0',
      '',
      '',
    );
    $raw_response = implode("\r\n", $raw_response);

    $this->parseResponseAndCheck($raw_response, 201, '');
  }

}
