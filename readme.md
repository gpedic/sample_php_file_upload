
##PHP Sample Application

Write a program that provides an HTTP API to store and retrieve files. It should support the following features:

- Upload a new file
- Retrieve an uploaded file by name
- Delete an uploaded file by name
- Bonus point: include a Vagrantfile so we can use Vagrant to try it out
- More bonus point: if multiple files have similar contents, reuse the contents somehow to save space.

You can use any framework or library you are comfortable with. We like Haskell, Nix and PHP so it'll be great if you use them, especially Nix.

What we look for in the project:

- Awareness of performance and security implications
- Clean code style
- Compliance with standards
- Enough documentation :)
- Appropriate use of tools.



#Notes

###Setup
The framework used for this sample API is Silex, all (composer) dependencies are installed as part of the VM provisioning so the app should be ready and running after Vagrant is finished everything up. The VM itself is available on the IP 10.10.10.10, the VM port 80 is mapped to local port 8080.

###Bonus: Vagrant
There is a Vagrant file included with the project which uses a custom NixOS image `(gpedic/nixos-16.03-x86_64)` which uses BTRFS as the default file system. BTRFS suits NixOS pretty well since it allows deduplication, which actually complements the immutable nature of NixOS that is the Nix package manager.

With [Vagrant](https://www.vagrantup.com/) installed setup should be as simple as running:
```
vagrant up
```
in the project root.

###Authentication
The auth token passed in the X-AUTH-TOKEN header is just the username and password, even this like basic auth would be reasonably secure if we provide HTTPS API endpoints only. If it was a production app this should be replaced by a dedicated revocable access token or a standardised auth solution JWT, OAuth, etc. depending on requirements.

There are 2 users included in this sample:
>user: alice, password: alice

>user: bob, password: bob

###Tests
The unit tests inside `tests/` are somewhat rudimentary since it's only a sample application, mostly just a run down of the API endpoints used to aid development.
```
$ cd app
$ ./vendor/bin/phpunit tests/Tests.php
```

###Security
These are the things we do to make the upload of arbitrary files reasonably secure:

 - Filename based attacks should not work here, files are not saved or retrieved under their original name but a hash of the filename to which we prepend the username
 - Files are stored outside the webroot and not accessible directly
 - Authentication is required to download files
 - Apache and PHP versions are not exposed

###Bonus: Deduplication
In short we make the file system do the work, BTRFS in this case.

The test data files in the `tests/` directory are crafted to showcase BTRFS block-based deduplication. Each row in the CSVs is 4096 bytes long, and the first 50 rows are identical which should result in a 50% space saving after running deduplication with [`duperemove`](https://github.com/markfasheh/duperemove) and a 4KB block size.

The configuration.nix contains a cron job that runs duperemove on the upload folder every 30 mins.
```
services.cron = {
    systemCronJobs = [
      "*/30 * * * * root ${pkgs.duperemove}/bin/duperemove -r -d -b 4096 /tmp/uploads"
    ];
  };
```
Example where 2 files share half their content is recognised by duperemove:
![Example where 2 files share half their content being recognised by duperemove](http://i.imgur.com/Vun2V4e.png)

#API usage examples

### Upload a file
```
$ curl -v -X POST -H "X-AUTH-TOKEN: alice:alice" -H "Content-Type: multipart/form-data" \
-F "file=@./app/tests/test-data-1.csv" http://localhost:8080/files
```

### Retrieve the uploaded file
```
$ curl -v -J -O -H "X-AUTH-TOKEN: alice:alice" http://localhost:8080/files/test-data-1.csv
```

### Delete the uploaded file
```
$ curl -v -X DELETE -H "X-AUTH-TOKEN: alice:alice" http://localhost:8080/files/test-data-1.csv
```
