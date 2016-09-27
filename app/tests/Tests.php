<?php
require_once __DIR__.'/../vendor/autoload.php';
use PHPUnit\Framework\TestCase;

class APITest extends TestCase
{
    protected static $client;
    protected static $testParams;

    public static function setUpBeforeClass ()
    {
        self::$client = new GuzzleHttp\Client([
            'base_uri' => 'http://localhost:8080'
        ]);

        self::$testParams = [
            [
              'token' => 'alice:alice',
              'file' => 'test-data-1.csv',
              'hash' => sha1_file(__DIR__ . '/test-data-1.csv')
            ],
            [
              'token' => 'bob:bob',
              'file' => 'test-data-2.csv',
              'hash' => sha1_file(__DIR__ . '/test-data-2.csv')
            ]
        ];
    }

    protected function generateUpload ($token, $file) {
        return self::$client->post('/files', [
            'headers' => [
                'X-AUTH-TOKEN' => $token
            ],
            'multipart' => [
                [
                    'Content-type' => 'multipart/form-data',
                    'name' => 'file',
                    'contents' => fopen(__DIR__. '/' .$file, 'r')
                ]
            ]
        ]);
    }

    protected function generateDownload ($token, $filename) {
        return self::$client->get('/files/' . $filename, [
            'headers' => [
                'X-AUTH-TOKEN' => $token
            ]
        ]);
    }

    //Upload the test files
    // curl -i -X POST -H "X-AUTH-TOKEN: alice:alice" -H "Content-Type: multipart/form-data" -F "file=@test-data-1.csv" http://localhost:8080/files
    public function testPostFiles() {
        foreach (self::$testParams as $upload) {
            $response = $this->generateUpload($upload['token'], $upload['file']);
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals('Success', $response->getBody());
        }
    }

    public function testGetFiles() {
        foreach (self::$testParams as $dl) {
            $response = $this->generateDownload($dl['token'], $dl['file']);
            $tmp = file_get_contents("php://input");
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertContains(
                'filename="'. $dl['file'] .'"',
                $response->getHeader('Content-Disposition')[0]
            );
            $this->assertEquals(sha1($response->getBody()), $dl['hash']);
        }
    }

    public function testDeleteFiles () {
        foreach (self::$testParams as $d) {
            $response = self::$client->delete('/files/' . $d['file'], [
                'headers' => [
                    'X-AUTH-TOKEN' => $d['token']
                ]
            ]);
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals('File deleted', $response->getBody());
        }
    }
}
