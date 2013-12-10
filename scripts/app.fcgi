#! /data/project/stimmberechtigung/bin/python
# -*- coding: utf-8 -*-

import flup.server.fcgi
import righttovote.web
import toolsweb

toolsweb.log_to_file(righttovote.web.app,
                     '/data/project/stimmberechtigung/error.log')

if __name__ == '__main__':
    flup.server.fcgi.WSGIServer(righttovote.web.app).run()

