<?php

namespace Minz;

/**
 * A View is a wrapper around a view file, useful to sandbox the template code.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class View
{
    /** @var string */
    private $filepath;

    /**
     * @param string $filepath
     */
    public function __construct($filepath)
    {
        $this->filepath = $filepath;
    }

    /**
     * Interpret a view file, giving access to an optional list of variables.
     *
     * @param mixed[] $variables
     *
     * @return string The content generated from the view file
     */
    public function build($variables = [])
    {
        foreach ($variables as $var_name => $var_value) {
            $$var_name = $var_value;
        }

        ob_start();
        include $this->filepath;
        $output = ob_get_clean();

        return $output;
    }
}
