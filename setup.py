import distutils.core

with open('requirements.txt') as f:
    reqs = f.read().splitlines()

distutils.core.setup(
    name='RightToVote',
    version='0.1dev',
    author='Robin Krahl',
    author_email='me@robin-krahl.de',
    packages=['righttovote'],
    package_data={'righttovote': ['templates/*.html']},
    license='LICENSE',
    long_description=open('README.md').read(),
    install_requires=reqs,
)

