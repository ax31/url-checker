<?php

namespace console\controllers;

use GuzzleHttp\Exception\RequestException;
use yii\console\Controller;
use common\models\UrlCheck;
use common\models\UrlCheckResult;
use GuzzleHttp\Client;

class UrlCheckerController extends Controller
{
    public function actionCheck()
    {
        $currentTime = time();
        $client = new Client([
            'timeout' => 10,
            'connect_timeout' => 5,
        ]);
        $urlChecks = UrlCheck::find()->all();

        foreach ($urlChecks as $check) {
            echo 'check ' . $check->id . "\n";
            $lastCheck = UrlCheckResult::find()
                ->where(['url_check_id' => $check->id])
                ->orderBy(['timestamp' => SORT_DESC])
                ->one();

            $nextCheckTime = $lastCheck
                ? strtotime($lastCheck->timestamp) + ($check->frequency * 60)
                : 0;

            if ($nextCheckTime > $currentTime) {
                continue;
            }

            for ($attempt = 1; $attempt <= $check->retry_count; $attempt++) {
                echo 'attempt ' . $attempt . "\n";
                try {
                    $response = $client->request('GET', $check->url);
                    $httpCode = $response->getStatusCode();

                    // Сохраняем результат, даже если это ошибка (например, 404)
                    $result = new UrlCheckResult([
                        'url_check_id' => $check->id,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'http_code' => $httpCode,
                        'response' => $response->getBody()->getContents(),
                        'attempt' => $attempt,
                    ]);
                    $result->save();

                    // Успешный запрос или корректная обработка ошибки (например, 404)
                    if ($httpCode >= 200 && $httpCode < 400) {
                        break; // Завершаем попытки, если запрос успешен
                    }
                } catch (RequestException $e) {
                    // Обработка исключений, связанных с запросами
                    $httpCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
                    $responseMessage = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();

                    $result = new UrlCheckResult([
                        'url_check_id' => $check->id,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'http_code' => $httpCode,
                        'response' => $responseMessage,
                        'attempt' => $attempt,
                    ]);
                    $result->save();

                    echo "Ошибка при проверке URL {$check->url}: HTTP-код {$httpCode}, сообщение: {$responseMessage}\n";

                    if ($attempt < $check->retry_count) {
                        sleep($check->retry_delay * 60);
                    }
                } catch (\Exception $e) {
                    // Обработка всех остальных ошибок
                    $result = new UrlCheckResult([
                        'url_check_id' => $check->id,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'http_code' => 0,
                        'response' => $e->getMessage(),
                        'attempt' => $attempt,
                    ]);
                    $result->save();

                    echo "Критическая ошибка при проверке URL {$check->url}: {$e->getMessage()}\n";

                    if ($attempt < $check->retry_count) {
                        sleep($check->retry_delay * 60);
                    }
                }
            }
        }
    }
}
