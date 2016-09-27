{ config, pkgs, ... }: with pkgs;
{
  environment.systemPackages =
  [
    git
    vim
    php56
    phpPackages.composer
  ];

  services.cron = {
    systemCronJobs = [
      "*/30 * * * * root ${pkgs.duperemove}/bin/duperemove -r -d -b 4096 /tmp/uploads"
    ];
  };

  networking.firewall.allowedTCPPorts = [ 22 80 ];

  services.httpd = {
    enable = true;
    enablePHP = true;
    adminAddr = "web1@example.org";
    extraConfig = ''
      ServerTokens ProductOnly
      ServerSignature Off
      Header unset X-Powered-By
    '';
    virtualHosts =
      [
        {
          documentRoot = "/var/www/sample/public";
          extraConfig = ''
            <Directory "/var/www/sample/public">
              DirectoryIndex index.php
              RewriteEngine On
              RewriteCond %{REQUEST_FILENAME} !-d
              RewriteCond %{REQUEST_FILENAME} !-f
              RewriteRule ^((?s).*)$ index.php?_url=/$1 [QSA,L]
            </Directory>
          '';
        }
      ];
  };
}
