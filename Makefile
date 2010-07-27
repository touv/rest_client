PEAR=pear
PHPUNIT=phpunit
XSLTPROC=xsltproc
CP=cp
MKDIR=mkdir
RM=rm

all : 
	@echo "try :"
	@echo "make release "
	@echo "make push"


test : REST_ClientTest REST_PullerTest

REST_ClientTest:
	$(PHPUNIT) $@ REST/ClientTest.php
REST_PullerTest:
	$(PHPUNIT) $@ REST/PullerTest.php

push:
	git push --tags

release: REST_Client-`./extract-version.sh`.tgz

REST_Client-`./extract-version.sh`.tgz: package.xml
	$(PEAR) package package.xml
	git tag -a -m "Version `./extract-version.sh`"  v`./extract-version.sh`
