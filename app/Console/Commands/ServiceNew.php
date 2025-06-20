<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Profile;

class ServiceNew extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'service:new';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crieate new service profile';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $this->info('Creating new service profile...');
        $name = $this->ask('Enter the name of the service profile');
        if (empty($name)) {
            $this->error('Name cannot be empty. Please enter a valid name.');
            return;
        }

        $username = $this->ask('Enter the username of the service profile');

        if (Profile::where('username', $username)->exists()) {
            $this->error('Username already exists. Please choose another one.');
            return;
        }

        $profile = new Profile();
        $profile->username = $username;
        $profile->name = $name;
        $pkiConfig = [
            'digest_alg'       => 'sha512',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $pki = openssl_pkey_new($pkiConfig);

        if ($pki === false) {
            throw new \Exception('Falha ao gerar par de chaves: ' . openssl_error_string());
        }

        openssl_pkey_export($pki, $pki_private);
        $pki_public = openssl_pkey_get_details($pki);
        $pki_public = $pki_public['key'];

        $profile->private_key = $pki_private;
        $profile->public_key = $pki_public;
        $profile->is_service = true; // Set the profile as a service profile
        $profile->is_private = true;
        $profile->save();

        $this->info('Service profile created successfully!');
        $this->info('Username: ' . $profile->username);
        $this->info('Name: ' . $profile->name);
        $this->info('Private Key: ' . $profile->private_key);
        $this->info('Public Key: ' . $profile->public_key);
        $this->info('You can now use this profile to manage service-related tasks.');


    }
}
