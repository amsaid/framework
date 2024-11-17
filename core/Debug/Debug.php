<?php

namespace Core\Debug;

class Debug
{
    private static ?array $queries = null;
    private static ?array $timeline = null;
    private static ?float $startTime = null;
    private static ?array $memory = null;
    private static bool $initialized = false;
    private static int $maxQueries = 20;
    private static int $maxTimelinePoints = 10;
    private static int $maxMemoryPoints = 10;
    private static int $maxQueryLength = 200;
    private static int $maxLabelLength = 30;

    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$queries = [];
        self::$timeline = [];
        self::$memory = [];
        self::$startTime = microtime(true);
        self::$initialized = true;
        
        // Register shutdown function to clean up
        register_shutdown_function([self::class, 'reset']);
    }

    public static function addQuery(string $query, float $time): void
    {
        if (!self::$initialized || !isset(self::$queries)) {
            return;
        }

        if (count(self::$queries) >= self::$maxQueries) {
            array_shift(self::$queries);
        }

        self::$queries[] = [
            'q' => substr(strip_tags($query), 0, self::$maxQueryLength),
            't' => round($time, 4)
        ];
    }

    public static function addTimelinePoint(string $label): void
    {
        if (!self::$initialized || !isset(self::$timeline, self::$startTime)) {
            return;
        }

        if (count(self::$timeline) >= self::$maxTimelinePoints) {
            array_shift(self::$timeline);
        }

        self::$timeline[] = [
            'l' => substr(strip_tags($label), 0, self::$maxLabelLength),
            't' => round(microtime(true) - self::$startTime, 4)
        ];
    }

    public static function addMemoryPoint(string $label): void
    {
        if (!self::$initialized || !isset(self::$memory)) {
            return;
        }

        if (count(self::$memory) >= self::$maxMemoryPoints) {
            array_shift(self::$memory);
        }

        self::$memory[] = [
            'l' => substr(strip_tags($label), 0, self::$maxLabelLength),
            'm' => memory_get_usage(true)
        ];
    }

    public static function renderDebugBar(): string
    {
        if (!self::$initialized || !isset(self::$queries, self::$timeline, self::$memory, self::$startTime)) {
            return '';
        }

        try {
            $stats = [
                't' => round(microtime(true) - self::$startTime, 4),
                'q' => count(self::$queries),
                'qt' => round(array_sum(array_column(self::$queries, 't')), 4),
                'm' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
            ];

            return 
                '<style>' . self::getStyles() . '</style>' .
                '<div id="d-bar" style="display:none">' .
                self::renderStats($stats) .
                self::renderTabs($stats['q']) .
                self::renderContent() .
                '</div>' .
                '<script>' . self::getScripts() . '</script>';
        } catch (\Throwable $e) {
            return '';
        }
    }

    private static function renderStats(array $stats): string
    {
        return sprintf(
            '<div class="d-sum">'.
            '<span>Time: %.2fs</span>'.
            '<span>Queries: %d</span>'.
            '<span>Query Time: %.2fs</span>'.
            '<span>Memory: %.1fMB</span>'.
            '</div>',
            $stats['t'],
            $stats['q'],
            $stats['qt'],
            $stats['m']
        );
    }

    private static function renderTabs(int $queryCount): string
    {
        return sprintf(
            '<div class="d-tabs">'.
            '<button class="d-tab active" data-tab="q">Queries (%d)</button>'.
            '<button class="d-tab" data-tab="t">Timeline</button>'.
            '<button class="d-tab" data-tab="m">Memory</button>'.
            '<button class="d-close" onclick="db.t()">×</button>'.
            '</div>',
            $queryCount
        );
    }

    private static function renderContent(): string
    {
        $html = '<div class="d-content active" id="d-q">' . self::renderQueries() . '</div>';
        $html .= '<div class="d-content" id="d-t">' . self::renderTimeline() . '</div>';
        $html .= '<div class="d-content" id="d-m">' . self::renderMemory() . '</div>';
        return $html;
    }

    private static function renderQueries(): string
    {
        if (empty(self::$queries)) {
            return '<p>No queries.</p>';
        }

        $html = '<table class="d-table"><tr><th>Query</th><th>Time</th></tr>';
        foreach (self::$queries as $q) {
            $html .= sprintf('<tr><td>%s</td><td>%.4fs</td></tr>', $q['q'], $q['t']);
        }
        return $html . '</table>';
    }

    private static function renderTimeline(): string
    {
        if (empty(self::$timeline)) {
            return '<p>No timeline data.</p>';
        }

        $html = '<div class="d-line">';
        $last = end(self::$timeline)['t'];
        foreach (self::$timeline as $t) {
            $pos = ($t['t'] / $last) * 100;
            $html .= sprintf(
                '<div class="d-point" style="left:%.1f%%" title="%s: %.3fs"></div>',
                min(100, $pos),
                $t['l'],
                $t['t']
            );
        }
        return $html . '</div>';
    }

    private static function renderMemory(): string
    {
        if (empty(self::$memory)) {
            return '<p>No memory data.</p>';
        }

        $html = '<div class="d-chart">';
        $max = max(array_column(self::$memory, 'm'));
        $width = 100 / count(self::$memory);
        
        foreach (self::$memory as $i => $m) {
            $height = ($m['m'] / $max) * 100;
            $mb = $m['m'] / 1024 / 1024;
            $html .= sprintf(
                '<div class="d-bar" style="left:%.1f%%;width:%.1f%%;height:%.1f%%" title="%s: %.1fMB"></div>',
                $i * $width,
                $width,
                $height,
                $m['l'],
                $mb
            );
        }
        return $html . '</div>';
    }

    private static function getStyles(): string
    {
        return '#d-bar{position:fixed;bottom:0;left:0;right:0;background:#222;color:#fff;font:12px monospace;z-index:9999}'.
               '.d-sum{display:flex;gap:20px;padding:8px;background:#333}'.
               '.d-tabs{display:flex;background:#444;padding:0 10px}'.
               '.d-tab{background:0;border:0;color:#aaa;padding:8px;cursor:pointer;border-bottom:2px solid transparent}'.
               '.d-tab:hover{color:#fff}'.
               '.d-tab.active{color:#fff;border-color:#0f0}'.
               '.d-content{display:none;padding:10px;max-height:200px;overflow:auto}'.
               '.d-content.active{display:block}'.
               '.d-table{width:100%;border-collapse:collapse}'.
               '.d-table td,.d-table th{padding:4px;text-align:left;border-bottom:1px solid #333}'.
               '.d-line,.d-chart{height:40px;background:#333;margin:10px 0;position:relative}'.
               '.d-point,.d-bar{position:absolute;background:#0f0;bottom:0}'.
               '.d-point{width:2px;height:100%}'.
               '.d-bar{border-radius:2px 2px 0 0}'.
               '.d-close{position:absolute;right:10px;background:0;border:0;color:#aaa;cursor:pointer;padding:8px}';
    }

    private static function getScripts(): string
    {
        return 'window.db={i:function(){document.querySelectorAll(".d-tab").forEach(t=>t.addEventListener("click",()=>{document.querySelectorAll(".d-tab,.d-content").forEach(e=>e.classList.remove("active"));t.classList.add("active");document.getElementById("d-"+t.dataset.tab).classList.add("active")}));document.addEventListener("keydown",e=>{if(e.altKey&&e.key.toLowerCase()==="d")this.t()})},t:function(){const d=document.getElementById("d-bar");d.style.display=d.style.display==="none"?"block":"none"}};db.i()';
    }

    public static function reset(): void
    {
        self::$queries = null;
        self::$timeline = null;
        self::$startTime = null;
        self::$memory = null;
        self::$initialized = false;
    }
}
