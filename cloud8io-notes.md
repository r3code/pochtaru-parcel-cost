#Setting up Git, Github and Cloud9 IDE and Pushing to live server
Dec 10, 2012 http://www.jimcode.org/2012/12/setting-git-github-cloud9-ide-pushing-live-server/

Cloud 9 to Git PushI thought it was about time I published another blog post and I have just been messing around trying to set up an ultra simple project using git for source control, Cloud9 IDE for the editor (which is all very simple) and then I thought wouldn’t it be nice to be able to push those changes live from the IDE. So, seemed like a simple thing to do and there were a few posts around on the net that helped but nothing that really pulled all of that together in once place so I thought it would be a perfect thing to explain.

I guess first off I should probably discourage people from pushing changes straight from their editor to a live server. There are obviously projects where that is appropriate and those where it is not. Me, messing around on a personal project with zero users and zero problem if things go wrong is obviously the former while making changes to a live site with millions of active users and a huge advertising spend is most certainly the latter! This capability is really useful and really powerful but should be handled with care – certainly it is a nice approach to push changes to a dev or testing server and perhaps take a little more care with an actual production environment.

Creating a new repository on github is very simple, so I won’t explain it all here – whether you create the repo on your local machine and hook it up with github or create it on github first is up to you but I am assuming you have a repo already on github and are ready to start editing using Cloud0 IDE.

[Cloud9 IDE][1] is a really cool project. Beyond that I haven’t yet had much chance to start using it but the appeal of being able to edit my code and push and pull directly from my git source control from my browser is fantastic. I think once you are working on projects like node.js or with hosting platforms like heroku it really gives you a whole lot more but for now I am just happy that I don’t have to set up my whole dev environment on every single box I want to work from. Signing up is simple and you can link it directly to your github account.

Once you have the github account linked in you can edit your code by just cloning the repository. The normal git controls are all built into the menu plus you can execute them from the command line (command line inside cloud 9 that is). So far so simple!

My next step was actually getting the code onto he server. Until now I have always satisfied myself with SSHing onto the server and doing a git clone or pull or similar and getting my latest code that way. I quite fancied the idea of being able to do it directly from cloud 9 though so I went about working out how that works.

First thing to do is to create a repository on the server you want the code to be pushed to. I did this by cloning my github repository but what you actually need is a bare repository, the easiest way for creating that (that I know of) is still to clone the github repo but then to shuffle the directory structure around a little and set the bare setting. The following bash commands shows how I do this.

git clone git@github.com:githubuser/githubrepo.git

And then move the content of the .git folder into it’s own folder and get rid of the rest.
```
cd githubrepo
mv .git .. && rm -fr * # move the git directory and delete the rest
mv ../.git .
mv .git/* .
rmdir .git # move all of the .git files into the main folder
git config --bool core.bare true # set the bare flag
cd ..; mv repo repo.git # renaming just for clarity
```
Now you can go back to the cloud 9 IDE and add a remote repository that you can push to. You do that with the Cloud 9 command line and the standard git commands. Note, this assumes you put the bare repository in your users home folder but you can actually put them anywhere.

```git remote add live username@server:~/githubrepo.git```
To push changes you can now run the following from Cloud 9 IDE.

```git push live master ```
Now the other half of this equation is getting the code from the bare repository to wherever it is supposed to go. In my case this is for deploying a website, so with a directory prepared and set-up with apache to serve the files we just need to checkout the latest version of the code from the bare repository whenever changes are pushed to it. This can be achieved using a post receive hook.
```
touch ~/githubrepo.git/hooks/post-receive
chmod +x ~/githubrepo.git/hooks/post-receive
vim ~/githubrepo.git/hooks/post-receive```
The file needs to be given execute permissions as above and have the following content. The path in there obviously depends on your server setup.
```
#!/bin/sh
GIT_WORK_TREE=/var/www/virtual.host.name/htdocs git checkout -f```
And that’s it. When you run the push live command from within Cloud9 IDE it now updates the bare repository and the hook runs a checkout to the directory you have selected. So you can now edit your code in Cloud 9 IDE and effortlessly deploy the code wherever you need it.

[1]: https://c9.io/