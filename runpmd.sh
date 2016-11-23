echo LINT my PHP code by PHP Mess Detector
pushd /home/ubuntu/workspace/
./vendor/bin/phpmd ./Pochtaru text codesize,unusedcode,naming
popd