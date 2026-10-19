<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/helper/DebugFlowHelper.php';

class IPSInfo extends IPSModuleStrict
{
    use \Burki24\SymconModuleHelper\DebugFlowHelper;

    private const ARCHIVE_CONTROL_MODULE_ID = '{43192F0B-135B-4CE7-A0A7-1475603F3060}';

    private const COUNTER_VARIABLES = [
        'IPSEvents' => ['IPS Events', 'IPS_GetEventList'],
        'IPSInstanzen' => ['IPS Instanzen', 'IPS_GetInstanceList'],
        'IPSKategorien' => ['IPS Kategorien', 'IPS_GetCategoryList'],
        'IPSLinks' => ['IPS Links', 'IPS_GetLinkList'],
        'IPSModule' => ['IPS Module (Gesamt)', 'IPS_GetModuleList'],
        'IPSModuleKern' => ['IPS Module (Kern Instanzen)', [self::class, 'getCoreModules']],
        'IPSModuleIO' => ['IPS Module (I/O Instanzen)', [self::class, 'getIoModules']],
        'IPSModuleSplitter' => ['IPS Module (Splitter Instanzen)', [self::class, 'getSplitterModules']],
        'IPSModuleGeraete' => ['IPS Module (Geräte Instanzen)', [self::class, 'getDeviceModules']],
        'IPSModuleKonfigurator' => ['IPS Module (Konfigurator Instanzen)', [self::class, 'getConfiguratorModules']],
        'IPSObjekte' => ['IPS Objekte', 'IPS_GetObjectList'],
        'IPSProfile' => ['IPS Profile', 'IPS_GetVariableProfileList'],
        'IPSSkripte' => ['IPS Skripte', 'IPS_GetScriptList'],
        'IPSVariablen' => ['IPS Variablen', 'IPS_GetVariableList'],
        'IPSMedien' => ['IPS Medien', 'IPS_GetMediaList'],
        'IPSLibrarys' => ['IPS Bibliotheken', 'IPS_GetLibraryList'],
    ];

    private const SIZE_VARIABLES = [
        'IPSScriptDirSize' => ['IPS Skripte in MB', 'scripts'],
        'IPSLogDirSize' => ['IPS Logs in MB', 'logs'],
        'IPSDBSize' => ['IPS Datenbank in MB', 'db'],
    ];

    public function Create(): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            parent::Create();

            $this->RegisterPropertyInteger('Intervall', 3600);
            $this->RegisterPropertyBoolean('wanipv4', false);
            $this->RegisterPropertyString('SymconAccountUser', '');
            $this->RegisterPropertyString('SymconAccountPassword', '');

            $this->RegisterTimer('ReadSysInfo', 0, 'IPSInfo_Update($_IPS[\'TARGET\']);');
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    public function ApplyChanges(): void
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            parent::ApplyChanges();

            $this->ensureIntegerProfile('IPSInfo.MB', ' MB', 0, 10240, 1);
            $this->registerVariables();

            $this->SetTimerInterval('ReadSysInfo', max(0, $this->ReadPropertyInteger('Intervall')) * 1000);
            $this->Update();
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, true);
        }
    }

    public function Update(): bool
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            if (!$this->isKernelReady()) {
                return $debugFlowResult = false;
            }

            $this->refreshObjectCounters();
            $this->refreshDirectorySizes();
            $this->refreshArchiveCounters();
            $this->refreshLicenseState();
            $this->refreshVersionState();
            $this->refreshSubscriptionState();

            if ($this->ReadPropertyBoolean('wanipv4')) {
                $this->GetWANIPv4();
            }

            return $debugFlowResult = true;
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function GetWANIPv4(): string|false
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $response = $this->loadTextFromUrl('https://api.ipify.org?format=text', 8);
            $address = is_string($response) ? trim($response) : '';

            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
                $this->DebugFlowEvent(__FUNCTION__, __LINE__, 'FEHLER', 'Es wurde keine gueltige WAN IPv4-Adresse empfangen.');
                return $debugFlowResult = false;
            }

            if ($this->ReadPropertyBoolean('wanipv4') && $this->hasRegisteredVariable('WAN_IPv4')) {
                $this->writeValue('WAN_IPv4', $address);
            }

            return $debugFlowResult = $address;
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function Get_WAN_IPv4(): string|false
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            return $debugFlowResult = $this->GetWANIPv4();
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    public function GetSubscriptionExpiration(): int|false
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $timestamp = $this->detectSubscriptionExpiration();
            if ($timestamp === null) {
                return $debugFlowResult = false;
            }

            $this->writeValue('SubscriptionAblaufVAR', $timestamp);
            return $debugFlowResult = $timestamp;
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    private function registerVariables(): void
    {
        $this->RegisterVariableString('IPSVersion', 'IPS Version');
        $this->RegisterVariableString('IPSLizenz', 'IPS Lizenz');
        $this->RegisterVariableString('IPSLizenzBenutzer', 'IPS Lizenz-Benutzername');
        $this->RegisterVariableString('IPSVersionBuild', 'IPS Version Build');
        $this->RegisterVariableFloat('IPSVersionMain', 'IPS Version Main');
        $this->RegisterVariableInteger('IPSVariablenLimit', 'IPS Variablen-Limit');

        foreach (self::COUNTER_VARIABLES as $ident => [$caption]) {
            $this->RegisterVariableInteger($ident, $caption);
        }

        foreach (self::SIZE_VARIABLES as $ident => [$caption]) {
            $this->RegisterVariableInteger($ident, $caption, 'IPSInfo.MB');
        }

        $this->RegisterVariableInteger('IPSDBAnzahlGeloggterVariablen', 'IPS Datenbank (Anzahl geloggter Variablen)');
        $this->RegisterVariableInteger('IPSDBAnzahlGeloggterWerteGesamt', 'IPS Datenbank (Anzahl geloggter Werte - Gesamt)');
        $this->RegisterVariableInteger('IPSStartTime', 'Letzter IPS-Start', '~UnixTimestamp');
        $this->RegisterVariableInteger('SubscriptionAblaufVAR', 'IPS Subscription - Ablaufdatum', '~UnixTimestamp');

        if ($this->ReadPropertyBoolean('wanipv4')) {
            $this->RegisterVariableString('WAN_IPv4', 'WAN - IPv4');
            return;
        }

        if ($this->hasRegisteredVariable('WAN_IPv4')) {
            $this->UnregisterVariable('WAN_IPv4');
        }
    }

    private function isKernelReady(): bool
    {
        $runlevel = IPS_GetKernelRunlevel();
        if ($runlevel === 10103) {
            return true;
        }

        $this->DebugFlowEvent(__FUNCTION__, __LINE__, 'AKTION', 'Aktualisierung übersprungen: Symcon-Kernel ist noch nicht betriebsbereit.', ['Runlevel' => $runlevel]);
        return false;
    }

    private function refreshObjectCounters(): void
    {
        foreach (self::COUNTER_VARIABLES as $ident => [, $provider]) {
            $items = $provider();
            $this->writeValue($ident, is_array($items) ? count($items) : 0);
        }
    }

    private function refreshDirectorySizes(): void
    {
        foreach (self::SIZE_VARIABLES as $ident => [, $directoryKey]) {
            $this->writeValue($ident, $this->calculateDirectoryMegabytes($this->getSystemDirectory($directoryKey)));
        }
    }

    private function refreshArchiveCounters(): void
    {
        $archiveId = $this->findFirstInstanceId(self::ARCHIVE_CONTROL_MODULE_ID);
        if ($archiveId === null) {
            $this->writeValue('IPSDBAnzahlGeloggterVariablen', 0);
            $this->writeValue('IPSDBAnzahlGeloggterWerteGesamt', 0);
            $this->DebugFlowEvent(__FUNCTION__, __LINE__, 'FEHLER', 'Archive Control wurde nicht gefunden.');
            return;
        }

        $variables = AC_GetAggregationVariables($archiveId, false);
        $records = array_reduce(
            $variables,
            static fn (int $sum, array $variable): int => $sum + (int) ($variable['RecordCount'] ?? 0),
            0
        );

        $this->writeValue('IPSDBAnzahlGeloggterVariablen', count($variables));
        $this->writeValue('IPSDBAnzahlGeloggterWerteGesamt', $records);
    }

    private function refreshLicenseState(): void
    {
        $limit = IPS_GetLimitVariables();
        $label = match ($limit) {
            0 => 'Unlimited',
            500 => 'Basic',
            1000 => 'Professional',
            default => 'Unbekannt',
        };

        $this->writeValue('IPSStartTime', IPS_GetKernelStartTime());
        $this->writeValue('IPSLizenzBenutzer', IPS_GetLicensee());
        $this->writeValue('IPSVariablenLimit', $limit);
        $this->writeValue('IPSLizenz', $label);
    }

    private function refreshVersionState(): void
    {
        $version = IPS_GetKernelVersion();
        $this->writeValue('IPSVersionMain', (float) $version);

        $build = $this->extractBuildNumber(IPS_GetLiveUpdateVersion());
        $this->writeValue('IPSVersionBuild', $build);
        $this->writeValue('IPSVersion', $version . ' #' . $build);
    }

    private function refreshSubscriptionState(): void
    {
        $timestamp = $this->detectSubscriptionExpiration();
        if ($timestamp === null) {
            $this->DebugFlowEvent(__FUNCTION__, __LINE__, 'FEHLER', 'Subscription-Ablaufdatum konnte nicht automatisch ermittelt werden.');
            return;
        }

        $this->writeValue('SubscriptionAblaufVAR', $timestamp);
    }

    private function detectSubscriptionExpiration(): ?int
    {
        return $this->readSubscriptionExpirationFromLocalFiles()
            ?? $this->readSubscriptionExpirationFromSymconAccount();
    }

    private function readSubscriptionExpirationFromLocalFiles(): ?int
    {
        $candidates = array_merge(
            glob(IPS_GetKernelDir() . '*license*') ?: [],
            glob(IPS_GetKernelDir() . '*License*') ?: []
        );

        foreach (array_unique($candidates) as $path) {
            if (!is_file($path) || filesize($path) > 1048576) {
                continue;
            }

            $content = @file_get_contents($path);
            if (!is_string($content)) {
                continue;
            }

            $timestamp = $this->extractSubscriptionExpiration($content);
            if ($timestamp !== null) {
                return $timestamp;
            }
        }

        return null;
    }

    private function readSubscriptionExpirationFromSymconAccount(): ?int
    {
        $username = trim($this->ReadPropertyString('SymconAccountUser'));
        $password = $this->ReadPropertyString('SymconAccountPassword');

        if ($username === '' || $password === '') {
            return null;
        }

        $page = $this->loadSymconLicensePage($username, $password);
        if ($page === false) {
            return null;
        }

        return $this->extractSubscriptionExpiration($page);
    }

    private function loadSymconLicensePage(string $username, string $password): string|false
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $cookieFile = tempnam(sys_get_temp_dir(), 'ipsinfo_');
            if ($cookieFile === false) {
                $this->DebugFlowEvent(__FUNCTION__, __LINE__, 'FEHLER', 'Cookie-Datei konnte nicht angelegt werden.');
                return $debugFlowResult = false;
            }

            $curl = curl_init();
            if ($curl === false) {
                @unlink($cookieFile);
                $this->DebugFlowEvent(__FUNCTION__, __LINE__, 'FEHLER', 'cURL konnte nicht initialisiert werden.');
                return $debugFlowResult = false;
            }

            $baseUrl = 'https://www.symcon.de/forum/';
            $commonOptions = [
                CURLOPT_HEADER => false,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_COOKIEFILE => $cookieFile,
                CURLOPT_COOKIEJAR => $cookieFile,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'IPS_Informations/2.0',
            ];

            curl_setopt_array($curl, $commonOptions + [
                CURLOPT_URL => $baseUrl . 'login.php?do=login',
                CURLOPT_REFERER => $baseUrl . 'index.php',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'vb_login_username' => $username,
                    'vb_login_password' => '',
                    's' => '',
                    'securitytoken' => 'guest',
                    'do' => 'login',
                    'vb_login_md5password' => md5($password),
                    'vb_login_md5password_utf' => md5($password),
                ]),
            ]);

            $this->DebugFlowEvent(__FUNCTION__, __LINE__, 'SENDEN', 'Anmeldung am Symcon-Konto wird ausgeführt; Zugangsdaten bleiben geschützt.');
            curl_exec($curl);

            curl_setopt_array($curl, [
                CURLOPT_POST => false,
                CURLOPT_URL => $baseUrl . 'live.php',
                CURLOPT_REFERER => $baseUrl . 'index.php',
            ]);

            $this->DebugFlowEvent(__FUNCTION__, __LINE__, 'SENDEN', 'Subscription-Seite wird über die angemeldete Sitzung abgefragt.');
            $page = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $this->DebugFlowEvent(__FUNCTION__, __LINE__, 'EMPFANG', 'Subscription-Schnittstelle hat geantwortet; Inhalt bleibt geschützt.', ['HTTPStatus' => $httpCode, 'AntwortBytes' => is_string($page) ? strlen($page) : 0]);

            curl_close($curl);
            @unlink($cookieFile);

            if ($httpCode === 200 && is_string($page) && $page !== '') {
                return $debugFlowResult = $page;
            }

            $this->DebugFlowEvent(__FUNCTION__, __LINE__, 'FEHLER', 'Subscription-Seite konnte nicht geladen werden.', ['HTTPStatus' => $httpCode]);
            return $debugFlowResult = false;
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    private function writeValue(string $ident, bool|int|float|string $value): bool
    {
        if (!$this->hasRegisteredVariable($ident) || $this->GetValue($ident) === $value) {
            return false;
        }

        $this->DebugFlowWriteValue($ident, $value, __FUNCTION__, __LINE__, !in_array($ident, ['WAN_IPv4', 'IPSLizenzBenutzer', 'IPSLizenz'], true));
        return true;
    }

    private function hasRegisteredVariable(string $ident): bool
    {
        return @$this->GetIDForIdent($ident) !== false;
    }

    private function findFirstInstanceId(string $moduleId): ?int
    {
        $ids = IPS_GetInstanceListByModuleID($moduleId);
        return $ids[0] ?? null;
    }

    private function getSystemDirectory(string $directoryKey): string
    {
        return match ($directoryKey) {
            'logs' => IPS_GetLogDir(),
            'scripts' => IPS_GetKernelDir() . 'scripts',
            'db' => IPS_GetKernelDir() . 'db',
        };
    }

    private function calculateDirectoryMegabytes(string $directory): int
    {
        if (!is_dir($directory)) {
            $this->DebugFlowEvent(__FUNCTION__, __LINE__, 'FEHLER', 'Ein benötigtes Systemverzeichnis fehlt; Größenwert wird als null gemeldet.');
            return 0;
        }

        $bytes = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file instanceof SplFileInfo && $file->isFile()) {
                $bytes += $file->getSize();
            }
        }

        return (int) round($bytes / 1048576);
    }

    private function loadTextFromUrl(string $url, int $timeoutSeconds): string|false
    {
        $debugFlowResult = null;
        $debugFlowFailed = false;
        $this->DebugFlowStart(__FUNCTION__, __LINE__);
        try {
            $this->DebugFlowEvent(__FUNCTION__, __LINE__, 'SENDEN', 'HTTP-Leseanfrage wird vorbereitet; Zieladresse und Inhalt bleiben geschützt.', ['ZeitlimitSekunden' => $timeoutSeconds]);
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => $timeoutSeconds,
                    'header' => "User-Agent: IPS_Informations\r\n",
                ],
            ]);

            $response = @file_get_contents($url, false, $context);
            $this->DebugFlowEvent(__FUNCTION__, __LINE__, $response === false ? 'FEHLER' : 'EMPFANG', $response === false ? 'HTTP-Leseanfrage fehlgeschlagen.' : 'HTTP-Leseanfrage beantwortet; Inhalt geschützt.', ['AntwortBytes' => is_string($response) ? strlen($response) : 0]);
            return $debugFlowResult = $response;
        } catch (\Throwable $debugFlowException) {
            $debugFlowFailed = true;
            $this->DebugFlowException(__FUNCTION__, __LINE__, $debugFlowException);
            throw $debugFlowException;
        } finally {
            $this->DebugFlowEnd(__FUNCTION__, __LINE__, $debugFlowResult, $debugFlowFailed, false);
        }
    }

    private function extractBuildNumber(string $liveUpdateVersion): string
    {
        if (preg_match('/(\d+)\s*$/', $liveUpdateVersion, $matches) === 1) {
            return $matches[1];
        }

        return trim($liveUpdateVersion);
    }

    private function parseGermanDate(string $value): ?int
    {
        $date = DateTimeImmutable::createFromFormat('!d.m.Y', $value);
        if ($date instanceof DateTimeImmutable) {
            return $date->getTimestamp();
        }

        $shortDate = DateTimeImmutable::createFromFormat('!d.m.y', $value);
        return $shortDate instanceof DateTimeImmutable ? $shortDate->getTimestamp() : null;
    }

    private function extractSubscriptionExpiration(string $content): ?int
    {
        $patterns = [
            '/(?:Subskription bis|Subscription until|valid until|gueltig bis|gültig bis)\s*:?\s*([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{2,4})/iu',
            '/(?:subscription|subskription|license|lizenz)[^0-9]{0,80}([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{2,4})/iu',
            '/(?:subscription|subskription|license|lizenz)[^0-9]{0,80}([0-9]{4}-[0-9]{2}-[0-9]{2})/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches) !== 1) {
                continue;
            }

            $timestamp = str_contains($matches[1], '-')
                ? $this->parseIsoDate($matches[1])
                : $this->parseGermanDate($matches[1]);

            if ($timestamp !== null) {
                return $timestamp;
            }
        }

        return null;
    }

    private function parseIsoDate(string $value): ?int
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date instanceof DateTimeImmutable ? $date->getTimestamp() : null;
    }

    private function ensureIntegerProfile(
        string $name,
        string $suffix,
        int $minimum,
        int $maximum,
        int $step
    ): void {
        if (!IPS_VariableProfileExists($name)) {
            IPS_CreateVariableProfile($name, 1);
        }

        $profile = IPS_GetVariableProfile($name);
        if ($profile['ProfileType'] !== 1) {
            $this->DebugFlowEvent(__FUNCTION__, __LINE__, 'FEHLER', 'Das Variablenprofil besitzt einen unpassenden Typ; Anpassung übersprungen.', ['Profil' => $name]);
            return;
        }

        IPS_SetVariableProfileText($name, '', $suffix);
        IPS_SetVariableProfileValues($name, $minimum, $maximum, $step);
    }

    private static function getCoreModules(): array
    {
        return IPS_GetModuleListByType(0);
    }

    private static function getIoModules(): array
    {
        return IPS_GetModuleListByType(1);
    }

    private static function getSplitterModules(): array
    {
        return IPS_GetModuleListByType(2);
    }

    private static function getDeviceModules(): array
    {
        return IPS_GetModuleListByType(3);
    }

    private static function getConfiguratorModules(): array
    {
        return IPS_GetModuleListByType(4);
    }
}
