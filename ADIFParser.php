<?php

class ADIFParser {
    protected array $header = [];
    protected array $QSOs = [];

    public static function loadFromFile(string $filename) {
        return (new self(file_get_contents($filename)));
    }

    public function __construct(private string $rawData) {
        $this->parse();
    }

    protected function parse() {
        [$rawHeaders, $rawQSOs] = preg_split('/<eoh>/i', $this->rawData);
        $this->parseHeaders($rawHeaders);
        $this->parseQSOs($rawQSOs);
    }

    protected function parseHeaders($rawHeaders) {}

    protected function parseQSOs($rawQSO) {
        $rawQSOs = preg_split('/<eor>/i', $rawQSO);
        foreach ($rawQSOs as $rawQSO) {
            $this->parseQSO($rawQSO);
        }
    }

    protected function parseQSO(string $rawQSO) {
        if (preg_match_all('/<([^:]+):(\d+)(?::([^>]+))?>([^><]*)/', $rawQSO, $matches, PREG_SET_ORDER)) {
            $QSO = [];
            foreach ($matches as $match) {
                $QSO[strtolower($match[1])] = [
                    'length' => $match[2],
                    'type' => $match[3],
                    'value' => trim($match[4]),
                ];
            }

            self::fixQSO($QSO);

            $this->QSOs[] = $QSO;
            // print_r($QSO);
        }
    }

    public function getQSOs() : array {
        return $this->QSOs;
    }

    public function getQSOCount() : int {
        return count($this->QSOs);
    }

    private static function fixQSO(array &$QSO) : void {
        if (array_key_exists('name', $QSO)) {
            $QSO['name']['value'] = self::fixUTF((string)$QSO['name']['value']);
            $QSO['name']['value'] = iconv('ISO-8859-1', 'ASCII//TRANSLIT//IGNORE', $QSO['name']['value']);
        }
    }

    private static function fixUTF(string $text) : string {
        return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
//        echo "$text\n";
//        if (preg_match_all('/([\x80-\xff]{4,6})/', $text, $matches, PREG_OFFSET_CAPTURE)) {
//            var_dump($matches[1]);
//
//            while ($item = array_pop($matches[1])) {
//                $search = $item[0];
//                for ($i = 0; $i < strlen($search); $i++) {
//                    echo dechex(ord($search[$i])) . ' ';
//                }
//                $replacement = mb_convert_encoding($search, 'ISO-8859-1', 'UTF-8');
//
//            }
//
//            echo PHP_EOL;
//        }
//        return $text;
    }
}
