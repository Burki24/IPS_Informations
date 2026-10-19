<?php

declare(strict_types=1);

const KR_READY = 10103;
function IPS_GetKernelRunlevel(): int
{
    return 10102;
}

class IPSModuleStrict
{
    public int $InstanceID = 12345;
    public array $values = ['IPSEvents' => 5, 'IPSLizenzBenutzer' => ''];
    public array $writes = [];
    public array $debug = [];
    public bool $failDebug = false;

    protected function GetIDForIdent(string $ident): int|false
    {
        return isset($this->values[$ident]) ? 23456 : false;
    }

    protected function GetValue(string $ident): mixed
    {
        return $this->values[$ident];
    }

    protected function SetValue(string $ident, mixed $value): bool
    {
        $this->values[$ident] = $value;
        $this->writes[] = [$ident, $value];
        return true;
    }

    protected function SendDebug(string $message, string $data, int $format): bool
    {
        if ($this->failDebug) {
            throw new RuntimeException('Debug unavailable.');
        }
        $this->debug[] = ['category' => $message, 'data' => json_decode($data, true, 512, JSON_THROW_ON_ERROR)];
        return true;
    }
}

require_once __DIR__ . '/../IPSInfo/module.php';

$info = new IPSInfo();
if ($info->Update() !== false || $info->writes !== []) {
    throw new RuntimeException('Startup gating must remain unchanged.');
}
$reader = new ReflectionMethod(IPSInfo::class, 'loadTextFromUrl');
$body = 'synthetic-private-account-body-token-123';
if ($reader->invoke($info, 'data://text/plain,' . $body, 8) !== $body) {
    throw new RuntimeException('Tracing must preserve the HTTP reader return value.');
}
$writer = new ReflectionMethod(IPSInfo::class, 'writeValue');
if ($writer->invoke($info, 'IPSEvents', 5) !== false || $writer->invoke($info, 'IPSEvents', 6) !== true) {
    throw new RuntimeException('Unchanged values must not be rewritten.');
}
$writer->invoke($info, 'IPSLizenzBenutzer', 'synthetic-private-license-user');
$text = json_encode($info->debug, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
if (str_contains($text, $body) || str_contains($text, 'synthetic-private-license-user') || str_contains($text, 'data://')) {
    throw new RuntimeException('Account bodies, license identities and URLs must not leak.');
}
if (!str_contains($text, 'HTTP-Leseanfrage beantwortet') || !str_contains($text, 'noch nicht betriebsbereit') || !str_contains($text, 'IPSEvents')) {
    throw new RuntimeException('System-state and communication decisions need German context.');
}
$stack = [];
foreach ($info->debug as $entry) {
    $key = [$entry['data']['Vorgang'], $entry['data']['Aufruf']];
    if (str_contains($entry['category'], ' | START | ')) {
        $stack[] = $key;
    } elseif (str_contains($entry['category'], ' | ENDE | ') && array_pop($stack) !== $key) {
        throw new RuntimeException('Unbalanced system-info trace.');
    }
}
if ($stack !== []) {
    throw new RuntimeException('Unclosed system-info trace.');
}
$info->failDebug = true;
if ($reader->invoke($info, 'data://text/plain,offline', 8) !== 'offline') {
    throw new RuntimeException('Debug failure changed the reader behavior.');
}
echo "IPSInfo debug-output checks passed.\n";
