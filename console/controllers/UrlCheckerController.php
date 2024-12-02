<?php

namespace console\controllers;

use common\models\UrlCheckResult;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use yii\console\Controller;
use common\models\UrlCheck;
use yii\db\Expression;

class UrlCheckerController extends Controller
{
    public function actionSendToQueue()
    {
        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        $channel->queue_declare('url_check_queue', false, true, false, false);

        $urlChecks = UrlCheck::find()->all();

        foreach ($urlChecks as $check) {
            $nextCheckTime = $check->last_check ? strtotime($check->last_check) + ($check->frequency * 60) : 0;

            if (time() < $nextCheckTime) {
                echo "URL {$check->url} ещё не нужно проверять.\n";
                continue;
            }

            $data = json_encode([
                'id' => $check->id,
                'url' => $check->url,
                'retry_count' => $check->retry_count,
                'retry_delay' => $check->retry_delay,
            ]);

            $msg = new AMQPMessage($data, ['delivery_mode' => 2]);
            $channel->basic_publish($msg, '', 'url_check_queue');

            $check->last_check = new Expression('NOW()');
            $check->save();

            echo "URL {$check->url} добавлен в очередь.\n";
        }

        $channel->close();
        $connection->close();
    }

    public function actionConsumeQueue()
    {
        $connection = new AMQPStreamConnection(
            'localhost',
            5672,
            'guest',
            'guest',
            '/',
            false,
            'AMQPLAIN',
            null,
            'en_US',
            60.0,
            60.0,
            null,
            false,
            30
        );

        $channel = $connection->channel();
        $channel->queue_declare('url_check_queue', false, true, false, false);

        echo "Ожидание задач...\n";

        $callback = function ($msg) use ($channel) {
            $data = json_decode($msg->body, true);

            if (!$data || !isset($data['url'])) {
                echo "Неверные данные задачи.\n";
                $channel->basic_ack($msg->delivery_info['delivery_tag']);
                return;
            }

            $url = $data['url'];
            $retryCount = $data['retry_count'] ?? 0;
            $retryDelay = $data['retry_delay'] ?? 1;

            echo "Обработка URL: {$url}\n";

            $attempt = 0;
            $success = false;

            while (!$success && $attempt <= $retryCount) {
                $attempt++;
                try {
                    $client = new Client(['timeout' => 10, 'connect_timeout' => 5, 'http_errors' => false]);
                    $response = $client->request('GET', $url);

                    $httpCode = $response->getStatusCode();
                    $body = $response->getBody()->getContents();

                    $result = new UrlCheckResult([
                        'url_check_id' => $data['id'],
                        'timestamp' => date('Y-m-d H:i:s'),
                        'http_code' => $httpCode,
                        'response' => $body,
                        'attempt' => $attempt,
                    ]);
                    $result->save();

                    echo "URL {$url} проверен успешно. HTTP-код: {$httpCode}\n";

                    $success = true;

                } catch (RequestException $e) {
                    $error = $e->getMessage();
                    echo "Ошибка на попытке {$attempt} для URL {$url}: {$error}\n";

                    if ($attempt <= $retryCount) {
                        sleep($retryDelay * 60);
                    } else {
                        $result = new UrlCheckResult([
                            'url_check_id' => $data['id'],
                            'timestamp' => date('Y-m-d H:i:s'),
                            'http_code' => 0,
                            'response' => $error,
                            'attempt' => $attempt,
                        ]);
                        $result->save();

                        echo "Все попытки для URL {$url} завершились неудачей.\n";
                    }
                } catch (Exception $e) {
                    $error = $e->getMessage();
                    echo "Непредвиденная ошибка на попытке {$attempt} для URL {$url}: {$error}\n";

                    if ($attempt <= $retryCount) {
                        sleep($retryDelay * 60);
                    } else {
                        $result = new UrlCheckResult([
                            'url_check_id' => $data['id'],
                            'timestamp' => date('Y-m-d H:i:s'),
                            'http_code' => 0,
                            'response' => "Непредвиденная ошибка: {$error}",
                            'attempt' => $attempt,
                        ]);
                        $result->save();

                        echo "Все попытки для URL {$url} завершились неудачей.\n";
                    }
                }
            }

            $channel->basic_ack($msg->delivery_info['delivery_tag']);
        };

        $channel->basic_consume('url_check_queue', '', false, false, false, false, $callback);

        while (count($channel->callbacks)) {
            try {
                $channel->wait();
            } catch (AMQPTimeoutException $e) {
                echo "Таймаут ожидания сообщения.\n";
            }
        }

        $channel->close();
        $connection->close();
    }
}
