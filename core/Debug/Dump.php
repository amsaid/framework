<?php

namespace Core\Debug;

class Dump
{
    private static int $depth = 0;
    private static array $objects = [];
    private static bool $initialized = false;
    private const MAX_DEPTH = 10;
    private const MAX_STRING_LENGTH = 1000;

    private static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        // Output CSS and JavaScript only once
        echo '<style>
            .dump-container {
                background: #1a1a1a;
                color: #e1e1e1;
                font-family: monospace;
                font-size: 14px;
                line-height: 1.5;
                padding: 15px;
                border-radius: 6px;
                margin: 10px 0;
                overflow-x: auto;
            }
            .dump-container pre {
                margin: 0;
                white-space: pre-wrap;
            }
            .dump-type {
                color: #0984e3;
                font-weight: bold;
            }
            .dump-value {
                color: #00b894;
            }
            .dump-string {
                color: #fdcb6e;
            }
            .dump-null {
                color: #d63031;
            }
            .dump-key {
                color: #74b9ff;
            }
            .dump-indent {
                margin-left: 20px;
            }
            .dump-toggle {
                cursor: pointer;
                color: #636e72;
                user-select: none;
            }
            .dump-toggle:hover {
                color: #b2bec3;
            }
            .dump-collapsed {
                display: none;
            }
            .dump-resource {
                color: #a29bfe;
            }
            .dump-modifier {
                color: #81ecec;
            }
            .dump-reference {
                color: #ffeaa7;
            }
            .dump-length {
                color: #636e72;
                font-style: italic;
            }
        </style>
        <script>
            function toggleDump(element) {
                const content = element.nextElementSibling;
                const isCollapsed = content.classList.contains("dump-collapsed");
                content.classList.toggle("dump-collapsed");
                element.textContent = isCollapsed ? "▼" : "▶";
            }
        </script>';

        self::$initialized = true;
    }

    public static function dump(...$vars): void
    {
        self::initialize();
        
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        $file = $trace['file'] ?? 'unknown';
        $line = $trace['line'] ?? 0;
        
        echo '<div class="dump-container">';
        echo "<pre><span class='dump-key'>Dump</span> in <span class='dump-value'>{$file}</span> on line <span class='dump-value'>{$line}</span></pre>";
        
        foreach ($vars as $var) {
            self::$depth = 0;
            self::$objects = [];
            echo '<pre>' . self::dumpVar($var) . '</pre>';
        }
        
        echo '</div>';
    }

    private static function dumpVar($var, string $keyPrefix = ''): string
    {
        if (self::$depth > self::MAX_DEPTH) {
            return '<span class="dump-value">*MAX DEPTH*</span>';
        }

        if (is_null($var)) {
            return '<span class="dump-type">null</span>';
        }

        if (is_bool($var)) {
            return '<span class="dump-type">bool</span>(<span class="dump-value">' . ($var ? 'true' : 'false') . '</span>)';
        }

        if (is_int($var)) {
            return '<span class="dump-type">int</span>(<span class="dump-value">' . $var . '</span>)';
        }

        if (is_float($var)) {
            return '<span class="dump-type">float</span>(<span class="dump-value">' . $var . '</span>)';
        }

        if (is_string($var)) {
            $length = strlen($var);
            $truncated = $length > self::MAX_STRING_LENGTH;
            if ($truncated) {
                $var = substr($var, 0, self::MAX_STRING_LENGTH) . '...';
            }
            $var = htmlspecialchars($var);
            return '<span class="dump-type">string</span>(<span class="dump-length">' . $length . '</span>) <span class="dump-string">"' . $var . '"</span>';
        }

        if (is_array($var)) {
            $output = '<span class="dump-type">array</span>(<span class="dump-length">' . count($var) . '</span>)';
            if (empty($var)) {
                return $output . ' []';
            }

            $toggleId = 'dump_' . uniqid();
            $output .= ' <span class="dump-toggle" onclick="toggleDump(this)">▼</span>';
            $output .= "\n<div class='dump-indent'>";

            self::$depth++;
            foreach ($var as $key => $value) {
                $output .= '<span class="dump-key">' . $key . '</span> => ' . self::dumpVar($value) . "\n";
            }
            self::$depth--;

            return $output . '</div>';
        }

        if (is_object($var)) {
            $hash = spl_object_hash($var);
            if (isset(self::$objects[$hash])) {
                return '<span class="dump-type">object</span>(<span class="dump-reference">*RECURSION*</span>)';
            }
            self::$objects[$hash] = true;

            $reflection = new \ReflectionObject($var);
            $className = $reflection->getName();
            $properties = self::getObjectProperties($var);

            $output = '<span class="dump-type">object</span>(<span class="dump-value">' . $className . '</span>)';
            if (empty($properties)) {
                return $output . ' {}';
            }

            $output .= ' <span class="dump-toggle" onclick="toggleDump(this)">▼</span>';
            $output .= "\n<div class='dump-indent'>";

            self::$depth++;
            foreach ($properties as $name => $value) {
                $modifier = '';
                if (strpos($name, "\0*\0") === 0) {
                    $name = substr($name, 3);
                    $modifier = '<span class="dump-modifier">protected</span> ';
                } elseif (strpos($name, "\0") === 0) {
                    $parts = explode("\0", $name);
                    $name = $parts[2];
                    $modifier = '<span class="dump-modifier">private</span> ';
                }
                $output .= $modifier . '<span class="dump-key">' . $name . '</span> => ' . self::dumpVar($value) . "\n";
            }
            self::$depth--;

            unset(self::$objects[$hash]);
            return $output . '</div>';
        }

        if (is_resource($var)) {
            return '<span class="dump-type">resource</span>(<span class="dump-resource">' . get_resource_type($var) . '</span>)';
        }

        return '<span class="dump-type">unknown type</span>';
    }

    private static function getObjectProperties($obj): array
    {
        $reflection = new \ReflectionObject($obj);
        $properties = [];

        do {
            foreach ($reflection->getProperties() as $property) {
                $property->setAccessible(true);
                $name = $property->getName();
                if ($property->isPrivate()) {
                    $name = "\0" . $reflection->getName() . "\0" . $name;
                } elseif ($property->isProtected()) {
                    $name = "\0*\0" . $name;
                }
                $properties[$name] = $property->getValue($obj);
            }
        } while ($reflection = $reflection->getParentClass());

        return $properties;
    }

    public static function dd(...$vars): void
    {
        self::dump(...$vars);
        exit(1);
    }
}
