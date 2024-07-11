import preset from '../../../../vendor/filament/filament/tailwind.config.preset';
import preset from '../../../../vendor/filament/filament/tailwind.config.preset';

export default {
    presets: [preset],
    content: [
        './app/Filament/Admin/**/*.php',
        './resources/views/filament/admin/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/kenepa/translation-manager/resources/**/*.blade.php',
    ],
}
