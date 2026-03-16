<?php

namespace ByJG\ApiTools\Exception;

use Exception;
use Throwable;

class BaseException extends Exception
{
    protected mixed $body;
    protected ?array $result;

    public function __construct(string $message = "", mixed $body = [], int $code = 0, ?Throwable $previous = null, ?array $result=null)
    {
        $this->body = $body;
        $this->result = $result;
        if (!empty($body)) {
            $message = $message . " ->\n" . (json_encode($body, JSON_PRETTY_PRINT) ?: 'null') . "\n".($result ? "\n".$this->doPrintFailure($result) : '');
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return mixed
     */
    public function getBody(): mixed
    {
        return $this->body;
    }

    public function printFailure() {
        if (isset($this->result)) {
            echo $this->doPrintFailure($this->result);
        }
    }

    protected function doPrintFailure(
        array $result, int $depth = 2, 
    ): string {
        $indent = 2;
        
        $strTimes = function($s, $n) {
            $r = '';
            for ($i=0; $i < $n; $i++) {
                $r .= $s;
            }
            return $r;
        };

        $hasFailed = !in_array($result['type'], ['object', 'array']) && !$result['match'];
            
        $margin = $hasFailed ? '!' : '>';
        $text = '';
        $type = $result['type'];

        if (in_array($type, ['string', 'bool', 'boolean', 'integer', 'float', 'number', 'null'])) {
            $text .= $margin.$strTimes(' ', $depth)." - ";
            $value = var_export($result['body'], true);

            if (strlen($value) > 50) {
                $value = substr($value, 0, 50).'...';
            }

            $text .= $value."\n";

            if (!$result['match']) {
                $text .= $margin.$strTimes(' ', $depth).'   '.($result['message'] ?? 'did not match '.$type)."\n";
            }
        }
        else if ($type === 'object' || $type === 'array') {
            if (!$result['match'] && !empty($result['message'])) {
                $text .= $margin.$strTimes(' ', $depth).' - '.($result['message'] ?? 'error '.$type)."\n";                
            }
            
            foreach ($result['subItems'] as $key => $subItemResult) {
                $text .= $margin.$strTimes(' ', $depth).var_export($key, true).": \n";
                $text .= $this->doPrintFailure($subItemResult, $depth + $indent);
            }
        }
        else if ($type === null) {
            $text = $margin.$strTimes(' ', $depth).var_export($result['forKey'], true).": \n";
            $depth += $indent;
            
            if (is_string($result['body']) || is_numeric($result['body']) || is_null($result['body'])) {
                $value = var_export($result['body'], true);
                if (strlen($value) > 50) {
                    $value = substr($value, 0, 50).'...';
                }
            }
            else {
                $value = var_export('<'.gettype($result['body']).'>');
            }
            $text .= $value."\n";

            if (!$result['match']) {
                $text .= $margin.$strTimes(' ', $depth).'   '.($result['message'] ?? 'did not match <no type>')."\n";
            }

            if (isset($result['failedItems'])) {
                $text .= $margin.$strTimes(' ', $depth)."\n";
                $text .= $margin.$strTimes(' ', $depth)."failed items: \n";
                
                foreach ($result['failedItems'] as $key => $subItemResult) {
                    $text .= $margin.$strTimes(' ', $depth).var_export($key, true).": \n";
                    $text .= $this->doPrintFailure($subItemResult, $depth + $indent);
                }
            }
        }

        return $text;
    }
}
