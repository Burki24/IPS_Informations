<?php

declare(strict_types=1);

class IPSInfo extends IPSModuleStrict
{
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
        parent::Create();

        $this->RegisterPropertyInteger('Intervall', 3600);
        $this->RegisterPropertyBoolean('wanipv4', false);
        $this->RegisterPropertyString('SubscriptionAblaufdatum', '');

        $this->RegisterTimer('ReadSysInfo', 0, 'IPSInfo_Update($_IPS[\'TARGET\']);');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $this->ensureIntegerProfile('IPSInfo.MB', ' MB', 0, 10240, 1);
        $this->registerVariables();

        $this->SetTimerInterval('ReadSysInfo', max(0, $this->ReadPropertyInteger('Intervall')) * 1000);
        $this->Update();
    }

    public function Update(): bool
    {
        if (!$this->isKernelReady()) {
            return false;
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

        return true;
    }

    public function GetWANIPv4(): string|false
    {
        $response = $this->loadTextFromUrl('https://api.ipify.org?format=text', 8);
        $address = is_string($response) ? trim($response) : '';

        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            $this->SendDebug(__FUNCTION__, 'Es wurde keine gueltige WAN IPv4-Adresse empfangen.', 0);
            return false;
        }

        if ($this->ReadPropertyBoolean('wanipv4') && $this->hasRegisteredVariable('WAN_IPv4')) {
            $this->writeValue('WAN_IPv4', $address);
        }

        return $address;
    }

    public function Get_WAN_IPv4(): string|false
    {
        return $this->GetWANIPv4();
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

        $this->SendDebug(__FUNCTION__, 'Kernel ist noch nicht bereit. Runlevel: ' . $runlevel, 0);
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
            $this->SendDebug(__FUNCTION__, 'Archive Control wurde nicht gefunden.', 0);
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
        $configuredDate = trim($this->ReadPropertyString('SubscriptionAblaufdatum'));
        if ($configuredDate === '') {
            return;
        }

        $timestamp = $this->parseGermanDate($configuredDate);
        if ($timestamp === null) {
            $this->SendDebug(__FUNCTION__, 'Bitte das Subscription-Datum im Format TT.MM.JJJJ eintragen.', 0);
            return;
        }

        $this->writeValue('SubscriptionAblaufVAR', $timestamp);
    }

    private function writeValue(string $ident, bool|int|float|string $value): bool
    {
        if (!$this->hasRegisteredVariable($ident) || $this->GetValue($ident) === $value) {
            return false;
        }

        $this->SetValue($ident, $value);
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
            $this->SendDebug(__FUNCTION__, 'Verzeichnis nicht gefunden: ' . $directory, 0);
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
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $timeoutSeconds,
                'header' => "User-Agent: IPS_Informations\r\n",
            ],
        ]);

        return @file_get_contents($url, false, $context);
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
            $this->SendDebug(__FUNCTION__, 'Variablenprofil hat einen unpassenden Typ: ' . $name, 0);
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
