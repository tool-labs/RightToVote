# -*- coding: utf-8 -*-

import flask
import toolsweb

app = toolsweb.create_app('RightToVote', template_package='righttovote')

@app.route('/')
def index():
    return flask.render_template('index.html')

