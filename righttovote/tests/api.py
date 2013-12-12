# -*- coding: utf-8 -*-

import datetime
from dateutil.relativedelta import relativedelta
import pytz
import righttovote.api
import unittest

class RightToVoteTests(unittest.TestCase):

    def setUp(self):
        self.rightToVote = righttovote.api.ApiRightToVote('ireas',
                'de.wikipedia.org')

    def testUserName(self):
        self.assertEqual(self.rightToVote.get_user_name(), 'Ireas')

    def testRegistration(self):
        result = self.rightToVote.get_registration()
        expected_result = datetime.datetime(2007, 1, 15, 16, 7, 16, 0,
                                            pytz.timezone('Europe/Berlin'))
        self.assertEqual(result, expected_result)

    def testUserId(self):
        self.assertEqual(self.rightToVote.get_user_id(), 336793)

    def testFirstEdit(self):
        result = self.rightToVote.get_first_edit()
        expected_result = datetime.datetime(2007, 1, 15, 16, 44, 31, 0,
                                            pytz.timezone('Europe/Berlin'))
        self.assertEqual(result, expected_result)

    def testContribCountLimit(self):
        result = self.rightToVote.get_contrib_count(1)
        self.assertEqual(result, 1)

    def testContribCountZeroPeriod(self):
        result = self.rightToVote.get_contrib_count(
            50,
            time=datetime.datetime(2006, 1, 1),
            delta=relativedelta(months=-2),
        )
        self.assertEqual(result, 0)

    def testContribCountNamespacePeriod(self):
        result = self.rightToVote.get_contrib_count(
            50,
            namespaces=['3'],
            time=datetime.datetime(2009, 04, 30),
            delta=relativedelta(days=-29),
        )
        self.assertEqual(result, 2)

    def testContribCountHighNamespace(self):
        result = self.rightToVote.get_contrib_count(500, namespaces=['0'])
        self.assertEqual(result, 500)

    def testContribCountLowNamespace(self):
        result = self.rightToVote.get_contrib_count(50, namespaces=['13'])
        self.assertTrue(result < 20)

def run():
    suite = unittest.TestLoader().loadTestsFromTestCase(RightToVoteTests)
    unittest.TextTestRunner(verbosity=2).run(suite)

if __name__ == '__main__':
    run()

