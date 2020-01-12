<?php

namespace Minz\Output;

/**
 * An Output represents the content returned to a user.
 *
 * It specifies the interface a class must implement to be usable by the
 * application.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
interface Output
{
    /**
     * Generate and return the content.
     *
     * @return string
     */
    public function render();

    /**
     * Returns the content type to set in HTTP headers
     *
     * @return string
     */
    public function contentType();
}
