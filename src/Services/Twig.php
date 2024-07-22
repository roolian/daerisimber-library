<?php

namespace DaerisimberLibrary\Services;

use Twig\TwigFunction;

class Twig
{
    public function __construct()
    {
        add_filter('timber/twig', [$this, 'add_to_twig']);
    }

    public function add_to_twig($twig)
    {
        $twig->addFunction(
            new TwigFunction('add_styles', [$this, 'add_styles'])
        );
        $twig->addFunction(
            new TwigFunction('print_styles', [$this, 'print_styles'])
        );

        return $twig;
    }

    public function add_styles($selector, $styles = [])
    {
        $compiled_styles[$selector] = [];

        foreach ($styles as $key => $value) {
            if (!empty($value)) {
                $compiled_styles[$selector][] = $key;
            }
        }

        return count($compiled_styles[$selector]) > 0 ? $compiled_styles : [];
    }

    public function print_styles($styles = [])
    {
        if(count($styles) <= 0) {
            return false;
        }

        $render = '<style type="text/css">';
        foreach ($styles as $selector => $rules) {
            $render .= $selector . '{';

            foreach ($rules as $rule) {
                $render .= $rule . ';';
            }

            $render .= '}';
        }
        $render .= '</style>';

        return $render;
    }

}
