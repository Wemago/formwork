<img <?= $this->attr([
            'class' => $class ?? null,
            'src'    => $user->image()?->uri() ?? Formwork\Users\InitialsImageGenerator::generate((string) $user->fullname()),
            'alt'    => $alt ?? $this->escapeAttr($user->username()),
            'width'  => 48,
            'height' => 48,
        ]) ?>>