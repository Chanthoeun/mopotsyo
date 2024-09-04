<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.organization', 'Patient Information Centre (MOPOTSYO)');
        $this->migrator->add('general.abbr', 'MOPOTSYO');
        $this->migrator->add('general.telephone', '+85517787992');
        $this->migrator->add('general.email', 'info@mopotsyo.org');
        $this->migrator->add('general.website', 'https://www.mopotsyo.org');
        $this->migrator->add('general.address', '#9E, Street 3C, Phum Trea 1, Stueng Meanchey 1 Commune,Meanchey District, Phnom Penh');
        $this->migrator->add('general.logo', '');
    }
};
