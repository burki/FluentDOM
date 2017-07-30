<?php

namespace FluentDOM\Loader {

  trait Supports {

    /**
     * @see Loadable::supports
     * @param string $contentType
     * @return bool
     */
    public function supports(string $contentType): bool {
      return (in_array(strtolower($contentType), $this->getSupported()));
    }

    /**
     * @return string[]
     */
    public function getSupported(): array {
      return [];
    }

    /**
     * Allow the loaders to validate the first part of the provided string.
     *
     * @param string $haystack
     * @param string $needle
     * @param bool $ignoreWhitespace
     * @return bool
     */
    private function startsWith(string $haystack, string $needle, bool $ignoreWhitespace = TRUE): bool {
      $pattern = $ignoreWhitespace
        ? '(^\s*'.preg_quote($needle).')'
        : '(^'.preg_quote($needle).')';
      return (bool)preg_match($pattern, $haystack);
    }
  }
}