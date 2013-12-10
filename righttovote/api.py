# -*- coding: utf-8 -*-

import datetime
from dateutil.relativedelta import relativedelta
import json
import urllib
import urllib2

class RightToVote():

    def get_first_edit(self):
        pass

    def get_user_name(self):
        pass

    def get_registration(self):
        pass

    def get_contrib_count(self, limit, namespaces=[], start=None, end=None):
        pass

    def check_ruleset(self, ruleset, base_datetime=datetime.datetime.now()):
        result = {}
        checks_result = True

        if 'first_edit' in ruleset:
            date = base_datetime + ruleset['first_edit']
            first_edit = self.get_first_edit()
            check_result = first_edit <= date
            result['first_edit_result'] = check_result
            result['first_edit_value'] = first_edit
            checks_result = check_result and checks_result

        if 'contrib_count' in ruleset:
            limit = ruleset['contrib_count']
            contrib_count = self.get_contrib_count(limit=limit)
            check_result = contrib_count >= limit
            result['contrib_count_result'] = check_result
            result['contrib_count_value'] = contrib_count
            checks_result = check_result and checks_result

        if 'recent_edits' in ruleset and 'recent_time' in ruleset:
            limit = ruleset['recent_edits']
            end = base_datetime
            start = end + ruleset['recent_time']
            recent_edits = self.get_contrib_count(limit=limit,
                                                  start=start,
                                                  end=end)
            check_result = recent_edits >= limit
            result['recent_edits_result'] = check_result
            result['recent_edits_value'] = recent_edits
            checks_result = check_result and checks_result

        if 'registration' in ruleset:
            date = base_datetime + ruleset['registration']
            registration = self.get_registration()
            check_result = registration <= date
            result['registration_result'] = check_result
            result['registration_value'] = registration
            checks_result = check_result and checks_result

        result['result'] = checks_result

        return result

class ApiRightToVote(RightToVote):

    DATE_FORMAT = '%Y-%m-%dT%H:%M:%SZ'

    def __init__(self, user_name, domain):
        self.user_name = user_name
        self.domain = domain

        user_data = self.get_user_data(user_name)
        self.user_name = user_data['name']
        self.user_id = user_data['userid']
        self.user_registration = datetime.datetime.strptime(
            user_data['registration'],
            ApiRightToVote.DATE_FORMAT
        )

    def get_contrib_count(self, limit, namespaces=[], start=None, end=None):
        args = {'action': 'query',
                'list': 'usercontribs',
                'uclimit': limit,
                'ucuser': self.user_name,
                'ucprop': '',
                'ucdir': 'newer'}
        if len(namespaces) > 0:
            args['ucnamespace'] = '|'.join(namespaces)
        if start is not None:
            args['ucstart'] = start.strftime(ApiRightToVote.DATE_FORMAT)
        if end is not None:
            args['ucend'] = end.strftime(ApiRightToVote.DATE_FORMAT)
        result = self._do_api_request(args)
        return len(result['query']['usercontribs'])

    def get_user_name(self):
        return self.user_name

    def get_registration(self):
        return self.user_registration

    def get_first_edit(self):
        args = {'action': 'query',
                'list': 'usercontribs',
                'ucdir': 'newer',
                'uclimit': '1',
                'ucuser': self.user_name}
        result = self._do_api_request(args)
        timestamp = result['query']['usercontribs'][0]['timestamp']
        return datetime.datetime.strptime(timestamp,
                                          ApiRightToVote.DATE_FORMAT)

    def get_user_data(self, user_name):
        args = {'action': 'query',
                'list': 'users',
                'usprop': 'registration',
                'ususers': user_name}
        result = self._do_api_request(args)
        return result['query']['users'][0]

    def _do_api_request(self, values):
        values['format'] = 'json'
        data = urllib.urlencode(values)
        url = 'https://{0}/w/api.php?{1}'.format(self.domain, data)
        response = urllib2.urlopen(url)
        result = response.read()
        return json.loads(result)

ruleset_de_arbcom = {
    'contrib_count': 400,
    'first_edit': relativedelta(months=-4),
}

ruleset_de_admin = {
    'contrib_count': 200,
    'first_edit': relativedelta(months=-2),
    'recent_edits': 50,
    'recent_time': relativedelta(years=-1),
}

ruleset_de_image = {
    'contrib_count': 60,
    'registration': relativedelta(months=-6),
}

