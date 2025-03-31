#!/bin/bash
if [ ! -d /var/faq_img ] ; then
	mkdir -p /var/faq_img
fi
if [ -d /var/faq_img ] ; then
	chown www-data.www-data /var/faq_img
fi
if [ ! -d ../faq_img ] ; then
	ln -s /var/faq_img ../faq_img
fi
if [ -d ../faq_img ] ; then
	chown www-data.www-data ../faq_img
	chmod -R 770 ../faq_img
fi

