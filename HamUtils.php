<?php

class HamUtils {
    public static function WWLocBigSquareDistance(string $loc1, string $loc2) : int {
        $loc1 = strtoupper($loc1);
        $loc2 = strtoupper($loc2);
        $loc1Lon = ord(substr($loc1, 0, 1)) * 10 + (int)substr($loc1, 2, 1);
        $loc2Lon = ord(substr($loc2, 0, 1)) * 10 + (int)substr($loc2, 2, 1);
        $loc1Lat = ord(substr($loc1, 1, 1)) * 10 + (int)substr($loc1, 3, 1);
        $loc2Lat = ord(substr($loc2, 1, 1)) * 10 + (int)substr($loc2, 3, 1);
        $lon = abs($loc2Lon - $loc1Lon);
        $lat = abs($loc1Lat - $loc2Lat);

        return max($lon, $lat);
    }

    public static function WWLocToDeg(string $loc) : ?array {
        $loc = strtoupper(trim($loc));
        if (!preg_match('/^[A-R]{2}\d{2}([A-X]{2}\d{2}[A-X]{2}|[A-X]{2}\d{2}|[A-X]{2})?$/', $loc)) {
            return null;
        }

        $lon = -180;
        $lat = -90;

        $lon += 20 * (ord(substr($loc, 0, 1)) - 65);
        $lat += 10 * (ord(substr($loc, 1, 1)) - 65);

        $lon += 2 * (int)substr($loc, 2, 1);
        $lat += 1 * (int)substr($loc, 3, 1);

        if (strlen($loc) > 4) {
            $lon += (5.0 / 60) * (ord(substr($loc, 4, 1)) - 65);
            $lat += (2.5 / 60) * (ord(substr($loc, 5, 1)) - 65);

            if (strlen($loc) === 6) {
                $lon += 2.5 / 60;
                $lat += 1.25 / 60;
            }
        }

        if (strlen($loc) > 6) {
            $lon += (0.5 / 60) * (int)substr($loc, 6, 1);
            $lat += (0.25 / 60) * (int)substr($loc, 7, 1);

            if (strlen($loc) === 8) {
                $lon += 0.5 / 2 / 60;
                $lat += 0.25 / 2 / 60;
            }
        }

        if (strlen($loc) > 8) { // not sure if this is correct
            $lon += (1.25 / 3600) * (ord(substr($loc, 8, 1)) - 65);
            $lat += (0.625 / 3600) * (ord(substr($loc, 9, 1)) - 65);

            if (strlen($loc) === 10) {
                $lon += 1.25 / 2  / 3600;
                $lat += 0.625 / 2 / 3600;
            }
        }

        return [$lon, $lat];
    }

    public static function LocDistance(float $lon1d, float $lat1d, float $lon2d, float $lat2d) : float {
        $lat1r = $lat1d / 180.0 * M_PI;
        $lon1r = $lon1d / 180.0 * M_PI;
        $lat2r = $lat2d / 180.0 * M_PI;
        $lon2r = $lon2d / 180.0 * M_PI;

        $lat1s = sin($lat1r);
        $lat2s = sin($lat2r);
        $lat1c = cos($lat1r);
        $lat2c = cos($lat2r);

        $lon0r = $lon2r - $lon1r;
        $lonc = cos($lon0r);
        $a = ($lat1s * $lat2s) + ($lat1c * $lat2c * $lonc);
        $sq = $a / sqrt(-$a * $a + 1);
        $b = -atan($sq) + M_PI / 2;
        return $b * 6371.299;
    }

    public static function WWLocDistance(string $loc1, string $loc2) : ?float {
        $loc1d = self::WWLocToDeg($loc1);
        $loc2d = self::WWLocToDeg($loc2);
        if ($loc1d === null || $loc2d === null) {
            return null;
        }

        return self::LocDistance($loc1d[0], $loc1d[1], $loc2d[0], $loc2d[1]);
    }

    public static function EDIMode(string $mode) : int {
        switch (strtoupper($mode)) {
            case 'SSB' : return 1;
            case 'AM' : return 5;
            case 'FM' : return 6;
            case 'RTTY' : return 7;
            case 'SSTV' : return 8;
            case 'ATV' : return 9;
            default : return 0;
        }
    }
    public static function exportSOTA(ADIFParser $adif, $config) : string {
        $result = '';

        foreach ($adif->getQSOs() as $record)
        {
            $record['rst_rcvd']['value'] = $record['rst_rcvd']['value'] ?? '--';
            $record['rst_sent']['value'] = $record['rst_sent']['value'] ?? '--';

            if (strlen($record['time_on']['value']) == 4) {
                $time = $record['time_on']['value'];
            }
            elseif (strlen($record['time_on']['value']) == 6) {
                $time = substr($record['time_on']['value'], 0, 4);
            }

            $date = substr($record['qso_date']['value'], 6, 2) . '/' . substr($record['qso_date']['value'], 4, 2) . '/' . substr($record['qso_date']['value'], 2, 2);

            $mySummit = '';
            if (preg_match('~my sota:\s?([a-z]{2}/[a-z]{2}-\d{3})~i', (string)$record['comment']['value'], $matches)) {
                $mySummit = $matches[1];
            }

            $hisSummit = '';
            if (preg_match('~s2s:\s?([a-z]{2}/[a-z]{2}-\d{3})~i', (string)$record['comment']['value'], $matches)) {
                $hisSummit = $matches[1];
            }

            $record['gridsquare']['value'] = strtoupper($record['gridsquare']['value']);
            $note = "{$record['gridsquare']['value']} rp{$record['rst_rcvd']['value']} rs{$record['rst_sent']['value']} ";
            if (isset($record['name']['value'])) {
                $note .= "OP:{$record['name']['value']}";
            }

            $result .= "V2,OM6RT,{$mySummit},{$date},{$time},{$record['freq_rx']['value']},{$record['mode']['value']},{$record['call']['value']},{$hisSummit},{$note}\n";
            //if (isset($record['sota_ref']['value'])) echo " SOTA:{$record['sota_ref']['value']}";
        }

        return $result;
    }

    public static function exportCBPRM(ADIFParser $adif, $config) : string {
        $result = '';

        foreach ($adif->getQSOs() as $record) {
            $record['rst_rcvd']['value'] = $record['rst_rcvd']['value'] ?? '--';
            $record['rst_sent']['value'] = $record['rst_sent']['value'] ?? '--';
            if (strlen($record['time_on']['value']) == 4) {
                $record['time_on']['value'] = substr($record['time_on']['value'], 0, 2) . ':' . substr($record['time_on']['value'], 2);
            }
            elseif (strlen($record['time_on']['value']) == 6) {
                $record['time_on']['value'] = substr($record['time_on']['value'], 0, 2) . ':' . substr($record['time_on']['value'], 2, 2) . ':' . substr($record['time_on']['value'], 4);
            }

            // print_r($record);
            $result .= "{$record['time_on']['value']} ";
            $result .= "{$record['call']['value']} {$record['gridsquare']['value']};rp{$record['rst_rcvd']['value']} rs{$record['rst_sent']['value']} {$record['band']['value']}-{$record['mode']['value']}";
            if (isset($record['name']['value'])) $result .= " OP:{$record['name']['value']}";
            if (isset($record['sota_ref']['value'])) $result .= " SOTA:{$record['sota_ref']['value']}";
            $result .= "\n";
        }

        return $result;
    }

    public static function exportVKVPA(ADIFParser $adif, $config) : string {
        $result = '';

        foreach ($adif->getQSOs() as $i => $record) {
            $record['rst_rcvd']['value'] = $record['rst_rcvd']['value'] ?? '--';
            $record['rst_sent']['value'] = $record['rst_sent']['value'] ?? '--';
            if (strlen($record['time_on']['value']) == 4) {
                $record['time_on']['value'] = substr($record['time_on']['value'], 0, 2) . ':' . substr($record['time_on']['value'], 2);
            }
            elseif (strlen($record['time_on']['value']) == 6) {
                $record['time_on']['value'] = substr($record['time_on']['value'], 0, 2) . ':' . substr($record['time_on']['value'], 2, 2) . ':' . substr($record['time_on']['value'], 4);
            }

            // print_r($record);
            $j = sprintf('%03d', $i + 1);
            $result .= "{$record['time_on']['value']} ";
            $result .= "{$record['call']['value']} {$record['gridsquare']['value']};rp{$record['rst_rcvd']['value']} rs{$record['rst_sent']['value']} {$record['band']['value']}-{$record['mode']['value']}";
            if (isset($record['name']['value'])) $result .= " #{$j}/#{$record['name']['value']}";
            if (isset($record['sota_ref']['value'])) $result .= " SOTA:{$record['sota_ref']['value']}";
            $result .= "\n";
        }

        return $result;
    }

    public static function exportPABody(ADIFParser $adif, $config) : string {
        $bodyZaSpojenia = 0;
        $lokatory = [];
        foreach ($adif->getQSOs() as $record) {
            $bodyZaSpojenia += HamUtils::WWLocBigSquareDistance($config['config']['common']['PWWLo'], $record['gridsquare']['value']) + 2;
            $lokator = strtoupper(substr($record['gridsquare']['value'], 0, 4));
            if (!in_array($lokator, $lokatory)) {
                $lokatory[] = $lokator;
            }
        }
        $rozneLokatory = count($lokatory);
        $vysledok = $bodyZaSpojenia * $rozneLokatory;

        // TODO zistit etapu z ADIFu
        return <<<EOF
Etapa:                september 2024
Značka:               {$config['config']['common']['PCall']}
Kategória:            {$config['config']['t:pabody']['Cat']}
Lokátor:              {$config['config']['common']['PWWLo']}
Počet spojení:        {$adif->getQSOCount()}
Body sa spojenia:     {$bodyZaSpojenia}
Počet lokátorov:      {$rozneLokatory}
Výsledok:             {$bodyZaSpojenia} x {$rozneLokatory} = {$vysledok} b.
EOF;
    }

    public static function exportSubreg(ADIFParser $adif, $config) : string {
        $dateFrom = $dateTo = '';

        $myLoc = strtoupper(substr($config['config']['common']['PWWLo'], 0, 6));

        $QSOPointsTotal = 0;
        $QOSLocs = [];
        $QSOs = [];
        $CODXC = ['', '', 0];
        $dateMin = '99999999';
        $dateMax = '';

        foreach ($adif->getQSOs() as $record) {
            $QOSLoc = strtoupper(substr($record['gridsquare']['value'], 0, 6));

            if ($myLoc == $QOSLoc) {
                $QSOPoints = 1;
            }
            else {
                $QSOPoints = floor(self::WWLocDistance($myLoc, $QOSLoc));
            }

            $QSOPointsTotal += $QSOPoints;

            if ($CODXC[2] < $QSOPoints) {
                $CODXC = [$record['call']['value'], $QOSLoc, $QSOPoints];
            }

            if (!in_array($QOSLoc, $QOSLocs)) {
                $QOSLocs[] = $QOSLoc;
            }

            $dateMin = min($dateMin, $record['qso_date']['value']);
            $dateMax = max($dateMax, $record['qso_date']['value']);

            $date = substr($record['qso_date']['value'], 2, 6);
            $mode = self::EDIMode($record['mode']['value']);
            $stx = sprintf('%03d', $record['stx']['value']);
            $srx = sprintf('%03d', $record['srx']['value']);

            $QSOs[] = "{$date};{$record['time_on']['value']};{$record['call']['value']};{$mode};{$record['rst_sent']['value']};{$stx};{$record['rst_rcvd']['value']};{$srx};;{$QOSLoc};{$QSOPoints};;;;";
        }
        $uniqueLocs = count($QOSLocs);
        $QSOs = implode("\n", $QSOs);

        return <<<EOF
[REG1TEST;1]
TName={$config['config']['t:subreg']['TName']}
TDate={$dateMin};{$dateMax}
PCall={$config['config']['common']['PCall']}
PWWLo={$myLoc}
PExch={$config['config']['t:subreg']['PExch']}
PSect={$config['config']['t:subreg']['PSect']}
PBand={$config['config']['t:subreg']['PBand']}
PClub=
RName={$config['config']['common']['RName']}
RCall={$config['config']['common']['RCall']}
RAdr1=
RAdr2=
RPoCo=
RCity=
RCoun=
RPhon=
RHBBS={$config['config']['common']['RHBBS']}
MOpe1={$config['config']['common']['MOpe1']}
STXEq={$config['config']['t:subreg']['STXEq']}
SPowe={$config['config']['t:subreg']['SPowe']}
SRXEq={$config['config']['t:subreg']['SRXEq']}
SAnte={$config['config']['t:subreg']['SAnte']}
SAntH={$config['config']['t:subreg']['SAntH']}
CQSOs={$adif->getQSOCount()};1
CQSOP={$QSOPointsTotal}
CWWLs={$uniqueLocs};0;1
CWWLB=0
CExcs=0;0;1
CExcB=0
CDXCs=1;0;1
CDXCB=0
CToSc={$QSOPointsTotal}
CODXC={$CODXC[0]};{$CODXC[1]};$CODXC[2]
[Remarks]
[QSORecords;{$adif->getQSOCount()}]
{$QSOs}
[END; OM6RT ADIFTransformer]
EOF;

    }
}
