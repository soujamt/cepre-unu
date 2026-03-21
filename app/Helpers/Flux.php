<?php

namespace App\Helpers;

/**
 * Clase de utilidad para construir y concatenar clases CSS dinámicamente.
 */
class Flux
{
    /**
     * Inicia una nueva colección de clases.
     * @return object
     */
    public static function classes()
    {
        // Usamos una clase anónima simple que implementa el método add()
        // y se puede convertir a string automáticamente.
        return new class {
            protected array $classes = [];

            /**
             * Añade clases a la colección.
             * @param string|array $classes
             * @return $this
             */
            public function add($classes)
            {
                if (is_string($classes) && !empty($classes)) {
                    // Si es una cadena, la añadimos directamente.
                    $this->classes[] = $classes;
                } elseif (is_array($classes)) {
                    // Si es un array, fusionamos los elementos.
                    $this->classes = array_merge($this->classes, $classes);
                }
                return $this;
            }

            /**
             * Convierte la colección en una cadena de clases única y filtrada.
             * @return string
             */
            public function __toString()
            {
                // Filtra vacíos, elimina duplicados y une con espacio.
                return implode(' ', array_unique(array_filter($this->classes)));
            }
        };
    }
}
