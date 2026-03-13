@props(['icon', 'class' => ''])

@php
    $iconClass = $icon ?? '';

    // Normalizar o ícone
    if ($iconClass) {
        // Se já tem o prefixo completo (fas fa-, far fa-, fab fa-, bi bi-), usar como está
        if (preg_match('/^(fas|far|fab|fal|fad|bi)\s+(fa-|bi-)/', $iconClass)) {
            // Já está no formato correto
        }
        // Se tem apenas fa- ou bi- no início, adicionar o estilo padrão
        elseif (str_starts_with($iconClass, 'fa-')) {
            $iconClass = 'fas ' . $iconClass;
        }
        elseif (str_starts_with($iconClass, 'bi-')) {
            $iconClass = 'bi ' . $iconClass;
        }
        // Se não tem nenhum prefixo, adicionar FontAwesome Solid como padrão
        else {
            $iconClass = 'fas fa-' . $iconClass;
        }
    }

    // Adicionar classes customizadas
    $finalClass = trim($iconClass . ' ' . $class);
@endphp

@if($icon)
    <i class="{{ $finalClass }}"></i>
@endif
