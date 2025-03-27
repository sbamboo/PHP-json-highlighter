<?php

#
#
# PHP Json Highligher v.0.0
# https://github.com/sbamboo/php-json-highlighter
#
# Simon Kalmi Claesson
# https://github.com/sbamboo
#
#

declare(strict_types=1);

class JsonHighlighter {
    private const CLASS_KEY      = 'json-key';      // Class for keys
    private const CLASS_STRING   = 'json-string';   // Class for strings
    private const CLASS_NUMBER   = 'json-number';   // Class for numbers
    private const CLASS_BOOL     = 'json-bool';     // Class for boolean values (true, false)
    private const CLASS_NULL     = 'json-null';     // Class for null
    private const CLASS_BRACKETS = 'json-brackets'; // Class for brackets/braces/commas/colons
    private const CLASS_COLON    = 'json-colon';    // Class for colons
    private const CLASS_COMMA    = 'json-comma';    // Class for commas
    private const CLASS_COMMENT  = 'json-comment';  // Class for comments
    private const STYLE_RESET    = '</span>';       // Closing the span tag
    // phpcs:ignore
    const JSON_HIGHLIGHT_REGEX = '/(?<key>"[^"]*")\s*:\s*|(?<string>"[^"\\\\]*(?:\\\\.[^"\\\\]*)*")|(?<number>-?\d+(?:\.\d+)?)|\b(?<bool>true|false)\b|\b(?<null>null)\b|(?<brackets>[{}[\]])|(?<colon>:)|(?<comma>,)|(?<comment_placeholder>__COMMENT_PLACEHOLDER_\d+__)/';
    private static array $comments = [];

    /**
     * Highlight JSON and return the HTML string.
     */
    public static function highlight(string $json): string {
        try {
            // Store and remove comments
            $json = self::extractComments($json);

            $highlightedJson = preg_replace_callback(
                self::JSON_HIGHLIGHT_REGEX,
                [self::class, 'replaceCallback'],
                $json
            );

            // Restore comments after highlighting
            return self::replacePlaceholdersWithComments($highlightedJson);
        } catch (Exception $e) {
            // Incase of error attempt to ensure no placeholders
            try {
                return self::replacePlaceholdersWithComments($highlightedJson);
            } catch (Exception $e) {
                return $json;
            }
        }
    }

    private static function extractComments(string $json): string {
        self::$comments = [];
        return preg_replace_callback(
            '/(?<comment>\/\*[\s\S]*?\*\/|\/\/.*?(\n|$))/',
            function ($matches) {
                $index                 = count(self::$comments);
                self::$comments[$index] = $matches['comment'];
                return '__COMMENT_PLACEHOLDER_' . $index . '__';
            },
            $json
        );
    }


    private static function replacePlaceholdersWithComments(string $json, bool $color = TRUE): string {
        foreach (self::$comments as $index => $comment) {
            $placeholder = '__COMMENT_PLACEHOLDER_' . $index . '__';
            if ($color == TRUE) {
                $highlightedComment = self::highlightDotDotDotComments($comment);
                $json = str_replace($placeholder, $highlightedComment, $json);
            } else {
                $json = str_replace($placeholder, $comment, $json);
            }
        }
        return $json;
    }

    private static function highlightDotDotDotComments(string $comment): string {
        // Regex to match "//..." (with optional surrounding whitespace)
        $pattern = '/^\/\/(\s*)(\.\.\.)(\s*)$/';

        if (preg_match($pattern, $comment, $matches)) {
            // If the entire comment is just "..." (with optional whitespace)
            return $matches[1] . '<span class="json-more-comment">' . $matches[2] . '</span>' . $matches[3];
        }

        // Return the original comment if it's not a "..." comment
        return $comment;
    }



    private static function replaceCallback(array $matches): string {
        if ($matches['key'] !== '') {
            return self::wrapInSpan($matches['key'], self::CLASS_KEY) .
                self::wrapInSpan(': ', self::CLASS_COLON);
        }

        if ($matches['string'] !== '') {
            return self::wrapInSpan($matches['string'], self::CLASS_STRING);
        }

        if ($matches['number'] !== '') {
            return self::wrapInSpan($matches['number'], self::CLASS_NUMBER);
        }

        if ($matches['bool'] !== '') {
            return self::wrapInSpan($matches['bool'], self::CLASS_BOOL);
        }

        if ($matches['null'] !== '') {
            return self::wrapInSpan($matches['null'], self::CLASS_NULL);
        }

        if ($matches['brackets'] !== '') {
            return self::wrapInSpan($matches['brackets'], self::CLASS_BRACKETS);
        }

        if ($matches['colon'] !== '') {
            return self::wrapInSpan($matches['colon'], self::CLASS_COLON);
        }

        if ($matches['comma'] !== '') {
            return self::wrapInSpan($matches['comma'], self::CLASS_COMMA);
        }

        if (isset($matches['comment_placeholder']) && $matches['comment_placeholder'] !== '') {
            // Leave placeholder for later replacement
            return $matches['comment_placeholder'];
        }

        return $matches[0];
    }

    private static function wrapInSpan(string $text, string $class): string {
        return '<span class="' . $class . '">' . $text . self::STYLE_RESET;
    }
}
