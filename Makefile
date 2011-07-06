PEAR=pear
PHPUNIT=phpunit
XSLTPROC=xsltproc
CP=cp
MKDIR=mkdir
RM=rm
VERSION=`./extract-version.sh`
CURVER=REST_Client-$(VERSION).tgz
APIKEY=5cd8785b-c05c-72d4-71f5-fa6fc9c39839
PEARHOST=http://pear.respear.net/respear/


all : 
	@echo "try :"
	@echo "make release "
	@echo "make push"


test : REST_ClientTest REST_EasyClientTest REST_ClientHookTest
test-stress : REST_ClientStressTest

REST_ClientTest:
	$(PHPUNIT) $@ REST/ClientTest.php
REST_ClientStressTest:
	$(PHPUNIT) $@ REST/ClientStressTest.php
REST_ClientHookTest:
	$(PHPUNIT) $@ REST/ClientHookTest.php
REST_EasyClientTest:
	$(PHPUNIT) $@ REST/EasyClientTest.php

push:
	git push
	git push --tags

release: tagging pearing

tagging: $(CURVER)
	git tag -a -m "Version $(VERSION)"  v$(VERSION)

pearing: $(CURVER)
	@read -p "Who are you ? " toto && cat $(CURVER) | curl -u `echo $$toto`:$(APIKEY) -X POST --data-binary @- $(PEARHOST)

$(CURVER): package.xml
	$(PEAR) package $?

