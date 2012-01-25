#!/bin/bash

pyrus install pear/PHP_CodeSniffer
phpenv rehash
phpcs --standard=build/phpcs.xml library/
