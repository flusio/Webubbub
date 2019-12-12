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

    /** @var string|null */
    private $template_name;

    /** @var mixed[] */
    private $template_variables;

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

        if ($this->template_name) {
            $template_filepath = self::templateFilepath($this->template_name);
            $view = new View($template_filepath);
            $this->template_variables['content'] = $output;
            $output = $view->build($this->template_variables);
        }

        return $output;
    }

    /**
     * Allow to set a template to the view.
     *
     * It must be called from within the view file directly.
     *
     * @param string $template_name The name of the file under src/templates/
     * @param mixed[] $template_variables A list of variables to pass to the template
     *
     * @throws \Minz\Errors\ViewError if the template file doesn't exist
     * @throws \Minz\Errors\ViewError if the template variables aren't an array
     */
    private function template($template_name, $template_variables = [])
    {
        $template_filepath = self::templateFilepath($template_name);
        if (!file_exists($template_filepath)) {
            throw new Errors\ViewError(
                "{$template_name} template file does not exist."
            );
        }

        if (!is_array($template_variables)) {
            throw new Errors\ViewError(
                "Template variables parameter must be an array."
            );
        }

        $this->template_name = $template_name;
        $this->template_variables = $template_variables;
    }

    /**
     * Helper to find the path to a template file
     *
     * @param string $template_name
     *
     * @return string
     */
    private static function templateFilepath($template_name)
    {
        $app_path = Configuration::$app_path;
        return "{$app_path}/src/templates/{$template_name}";
    }
}
