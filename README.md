# pochtaru-parcel-cost
The php wrapper for the new pochta.ru/parcel delivery cost calculator.

## Setup Cloud9 Environment

### Install PHPUnit 4.8 for Cloud9 php5.5.9 run
```
#create %user%/bin
mkdir ~/bin/
echo "bin" >> .gitignore

# Install old stable HPUnit 4.8 for correct work with Cloud9 php5.5.9
wget https://phar.phpunit.de/phpunit-old.phar -P ~/bin/

#make executable
chmod +x ~/bin/phpunit.phar

#add symlink
ln -s ~/bin/phpunit.phar ~/bin/phpunit

#check phpunit installed
 phpunit --version
 ```
 
Inspired by https://gist.github.com/mikedfunk/5146798

### Set Apache output encoding 
Enable "Show Hidden Files" and create .htaccess file in the root foolder. 
Add this line to it:
```
AddDefaultCharset utf-8
```

### Push to Github
To push code to Github do http://www.jimcode.org/2012/12/setting-git-github-cloud9-ide-pushing-live-server/
    