<?php

namespace App\Domains\Install\Services;

use RuntimeException;

class EnvironmentWriter
{
    /**
     * @param  array<string, string>  $values
     */
    public function write(array $values): void
    {
        $path = base_path('.env');
        $source = is_file($path) ? $path : base_path('.env.example');
        $contents = is_file($source) ? file_get_contents($source) : '';

        if ($contents === false) {
            throw new RuntimeException('The environment file could not be read.');
        }

        $lineEnding = str_contains($contents, "\r\n") ? "\r\n" : "\n";

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->quote($value);
            $pattern = '/^[ \t]*#?[ \t]*'.preg_quote($key, '/').'[ \t]*=.*$/m';

            if (preg_match($pattern, $contents) === 1) {
                $contents = (string) preg_replace_callback(
                    $pattern,
                    static fn (): string => $line,
                    $contents,
                    1
                );
            } else {
                $contents = rtrim($contents).$lineEnding.$line.$lineEnding;
            }
        }

        $temporary = $path.'.'.bin2hex(random_bytes(6)).'.tmp';

        if (file_put_contents($temporary, $contents, LOCK_EX) === false) {
            throw new RuntimeException('The environment file could not be written.');
        }

        $this->replaceEnvironmentFile($temporary, $path);
    }

    private function quote(string $value): string
    {
        $escaped = str_replace(
            ["\\", '"', '$', "\r", "\n"],
            ["\\\\", '\\"', '\\$', '', '\\n'],
            $value
        );

        return '"'.$escaped.'"';
    }

    private function replaceEnvironmentFile(string $temporary, string $path): void
    {
        if (@rename($temporary, $path)) {
            @chmod($path, 0600);

            return;
        }

        $backup = $path.'.'.bin2hex(random_bytes(6)).'.backup';

        if (! is_file($path) || ! @rename($path, $backup)) {
            @unlink($temporary);

            throw new RuntimeException('The environment file could not be finalized.');
        }

        if (! @rename($temporary, $path)) {
            @rename($backup, $path);
            @unlink($temporary);

            throw new RuntimeException('The environment file could not be finalized.');
        }

        @unlink($backup);
        @chmod($path, 0600);
    }
}
