<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // delete if exists befor add
        $this->migrator->deleteIfExists('general.organization');
        $this->migrator->deleteIfExists('general.abbr');
        $this->migrator->deleteIfExists('general.telephone');
        $this->migrator->deleteIfExists('general.email');
        $this->migrator->deleteIfExists('general.website');
        $this->migrator->deleteIfExists('general.address');
        $this->migrator->deleteIfExists('general.logo');
        $this->migrator->deleteIfExists('general.icon');

        // add
        $this->migrator->add('general.organization', 'Patient Information Centre (MOPOTSYO)');
        $this->migrator->add('general.abbr', 'MOPOTSYO');
        $this->migrator->add('general.telephone', '+85517787992');
        $this->migrator->add('general.email', 'info@mopotsyo.org');
        $this->migrator->add('general.website', 'https://www.mopotsyo.org');
        $this->migrator->add('general.address', '#9E, Street 3C, Phum Trea 1, Stueng Meanchey 1 Commune,Meanchey District, Phnom Penh');
        $this->migrator->add('general.logo', '');
        $this->migrator->add('general.icon', '');
    }
};
