# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure(2) do |config|

  config.vm.box = "gpedic/nixos-16.03-x86_64"

  # set hostname
   config.vm.hostname = "nixbox"

  # Disable automatic box update checking. If you disable this, then
  # boxes will only be checked for updates when the user runs
  # `vagrant box outdated`. This is not recommended.
  # config.vm.box_check_update = false

  # Create a forwarded port mapping which allows access to a specific port
   config.vm.network "forwarded_port", guest: 80, host: 8080

  # Create a private network, which allows host-only access to the machine
  # using a specific IP.
   config.vm.network "private_network", ip: "10.10.10.10"

   config.vm.provider "virtualbox" do |vb|
     # Customize the amount of memory on the VM:
     vb.memory = "1024"
   end

   config_path = "configuration.nix"
   config.vm.provision :nixos, run: 'always', :path => config_path
   config.vm.provision "shell", path: "provision.sh"

   config.vm.synced_folder "app/", "/var/www/sample", group: "wwwrun", owner: "wwwrun"

end
