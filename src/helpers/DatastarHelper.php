<?php

namespace justinholtweb\smoke\helpers;

use Craft;
use yii\web\Response;

/**
 * DataStar Helper
 *
 * Formats responses as DataStar SSE events
 */
class DatastarHelper
{
    /**
     * Format a response as DataStar SSE events
     */
    public static function response(array $events): Response
    {
        $response = Craft::$app->getResponse();
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');

        $data = '';

        // Fragment events (DOM patches)
        if (isset($events['fragment'])) {
            $data .= "event: datastar-fragment\n";
            $data .= 'data: ' . json_encode($events['fragment']) . "\n\n";
        }

        // Signal events (state updates)
        if (isset($events['signal'])) {
            $data .= "event: datastar-signal\n";
            $data .= 'data: ' . json_encode($events['signal']) . "\n\n";
        }

        $response->data = $data;

        return $response;
    }
}
