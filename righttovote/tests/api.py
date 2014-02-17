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

    def testRulesetDeAdmin(self):
        result = self.rightToVote.check_ruleset(
                righttovote.api.ruleset_de_admin,
                datetime.datetime(2014, 1, 1, 0, 0, 0, 0,
                                  pytz.timezone('Europe/Berlin')),
        )

        self.assertIn('result', result)
        self.assertIn('id', result)
        self.assertIn('rules', result)
        self.assertIn('contrib_count_result', result)
        self.assertIn('contrib_count_value', result)
        self.assertIn('first_edit_result', result)
        self.assertIn('first_edit_value', result)
        self.assertIn('recent_edits_result', result)
        self.assertIn('recent_edits_value', result)

        self.assertEqual(result['id'], 'de-admin')
        self.assertEqual(
                sorted(result['rules']),
                sorted(['contrib_count', 'first_edit', 'recent_edits']),
        )
        self.assertEqual(result['contrib_count_value'], 200)
        self.assertEqual(result['first_edit_value'],
                         datetime.datetime(2007, 1, 15, 16, 44, 31, 0,
                                           pytz.timezone('Europe/Berlin')))
        self.assertEqual(result['recent_edits_value'], 50)

        self.assertTrue(result['contrib_count_result'])
        self.assertTrue(result['first_edit_result'])
        self.assertTrue(result['recent_edits_result'])
        self.assertTrue(result['result'])

        result = self.rightToVote.check_ruleset(
                righttovote.api.ruleset_de_admin,
                datetime.datetime(2012, 11, 17, 0, 0, 0, 0,
                                  pytz.utc),
        )

        self.assertIn('result', result)
        self.assertIn('id', result)
        self.assertIn('rules', result)
        self.assertIn('contrib_count_result', result)
        self.assertIn('contrib_count_value', result)
        self.assertIn('first_edit_result', result)
        self.assertIn('first_edit_value', result)
        self.assertIn('recent_edits_result', result)
        self.assertIn('recent_edits_value', result)

        self.assertEqual(result['id'], 'de-admin')
        self.assertEqual(
                sorted(result['rules']),
                sorted(['contrib_count', 'first_edit', 'recent_edits']),
        )
        self.assertEqual(result['contrib_count_value'], 200)
        self.assertEqual(result['first_edit_value'],
                         datetime.datetime(2007, 1, 15, 16, 44, 31, 0,
                                           pytz.timezone('Europe/Berlin')))
        self.assertEqual(result['recent_edits_value'], 43)

        self.assertTrue(result['contrib_count_result'])
        self.assertTrue(result['first_edit_result'])
        self.assertFalse(result['recent_edits_result'])
        self.assertFalse(result['result'])

    def testRulesetDeArbcom(self):
        result = self.rightToVote.check_ruleset(
                righttovote.api.ruleset_de_arbcom,
                datetime.datetime(2014, 1, 1, 0, 0, 0, 0,
                                  pytz.timezone('Europe/Berlin')),
        )

        self.assertIn('result', result)
        self.assertIn('id', result)
        self.assertIn('rules', result)
        self.assertIn('contrib_count_result', result)
        self.assertIn('contrib_count_value', result)
        self.assertIn('first_edit_result', result)
        self.assertIn('first_edit_value', result)
        self.assertIn('dependency_result', result)
        self.assertIn('dependency_value', result)
        self.assertIn('id', result['dependency_value'])

        self.assertEqual(result['id'], 'de-arbcom')
        self.assertEqual(sorted(result['rules']),
                         sorted(['contrib_count', 'first_edit', 'dependency']))
        self.assertEqual(result['contrib_count_value'], 400)
        self.assertEqual(result['first_edit_value'],
                         datetime.datetime(2007, 1, 15, 16, 44, 31, 0,
                                           pytz.timezone('Europe/Berlin')))
        self.assertEqual(result['dependency_value']['id'], 'de-admin')

        self.assertTrue(result['contrib_count_result'])
        self.assertTrue(result['first_edit_result'])
        self.assertTrue(result['dependency_result'])
        self.assertTrue(result['result'])

        result = self.rightToVote.check_ruleset(
                righttovote.api.ruleset_de_arbcom,
                datetime.datetime(2012, 11, 17, 0, 0, 0, 0,
                                  pytz.utc),
        )

        self.assertIn('result', result)
        self.assertIn('id', result)
        self.assertIn('rules', result)
        self.assertIn('contrib_count_result', result)
        self.assertIn('contrib_count_value', result)
        self.assertIn('first_edit_result', result)
        self.assertIn('first_edit_value', result)
        self.assertIn('dependency_result', result)
        self.assertIn('dependency_value', result)
        self.assertIn('id', result['dependency_value'])

        self.assertEqual(result['id'], 'de-arbcom')
        self.assertEqual(sorted(result['rules']),
                         sorted(['contrib_count', 'first_edit', 'dependency']))
        self.assertIsInstance(result['contrib_count_value'], (int, long))
        self.assertEqual(result['contrib_count_value'], 400)
        self.assertEqual(result['first_edit_value'],
                         datetime.datetime(2007, 1, 15, 16, 44, 31, 0,
                                           pytz.timezone('Europe/Berlin')))
        self.assertEqual(result['dependency_value']['id'], 'de-admin')

        self.assertTrue(result['contrib_count_result'])
        self.assertTrue(result['first_edit_result'])
        self.assertFalse(result['dependency_result'])
        self.assertFalse(result['result'])

    def testRulesetDeImage(self):
        result = self.rightToVote.check_ruleset(
                righttovote.api.ruleset_de_image,
                datetime.datetime(2014, 1, 1, 0, 0, 0, 0,
                                  pytz.timezone('Europe/Berlin')),
        )

        self.assertIn('result', result)
        self.assertIn('id', result)
        self.assertIn('rules', result)
        self.assertIn('contrib_count_result', result)
        self.assertIn('contrib_count_value', result)
        self.assertIn('registration_result', result)
        self.assertIn('registration_value', result)

        self.assertEqual(result['id'], 'de-image')
        self.assertEqual(
                sorted(result['rules']),
                sorted(['contrib_count', 'registration']),
        )
        self.assertEqual(result['contrib_count_value'], 60)
        self.assertEqual(
                result['registration_value'],
                datetime.datetime(2007, 1, 15, 16, 7, 16, 0,
                                  pytz.timezone('Europe/Berlin')),
        )

        self.assertTrue(result['contrib_count_result'])
        self.assertTrue(result['registration_result'])
        self.assertTrue(result['result'])

        result = self.rightToVote.check_ruleset(
                righttovote.api.ruleset_de_image,
                datetime.datetime(2007, 2, 15, 0, 0, 0, 0, pytz.utc),
        )

        self.assertIn('result', result)
        self.assertIn('id', result)
        self.assertIn('rules', result)
        self.assertIn('contrib_count_result', result)
        self.assertIn('contrib_count_value', result)
        self.assertIn('registration_result', result)
        self.assertIn('registration_value', result)

        self.assertEqual(result['id'], 'de-image')
        self.assertEqual(
                sorted(result['rules']),
                sorted(['contrib_count', 'registration']),
        )
        self.assertEqual(result['contrib_count_value'], 46)
        self.assertEqual(
                result['registration_value'],
                datetime.datetime(2007, 1, 15, 16, 7, 16, 0,
                                  pytz.timezone('Europe/Berlin')),
        )

        self.assertFalse(result['contrib_count_result'])
        self.assertFalse(result['registration_result'])
        self.assertFalse(result['result'])

def run():
    suite = unittest.TestLoader().loadTestsFromTestCase(RightToVoteTests)
    unittest.TextTestRunner(verbosity=2).run(suite)

if __name__ == '__main__':
    run()

