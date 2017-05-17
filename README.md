Syrup
========================

[![Build Status](https://travis-ci.org/keboola/syrup.svg?branch=master)](https://travis-ci.org/keboola/syrup)
[![Code Climate](https://codeclimate.com/github/keboola/syrup/badges/gpa.svg)](https://codeclimate.com/github/keboola/syrup)
[![Test Coverage](https://codeclimate.com/github/keboola/syrup/badges/coverage.svg)](https://codeclimate.com/github/keboola/syrup)


Syrup is a framework for rapid development of Keboola Connection components (i.e. extractors).
It is based on Symfony2 framework.

Development
----------------------------------

- Clone from GitHub:
```bash
git clone https://github.com/keboola/syrup.git
```

- Create `app/config/parameters.yml` from `paramters.yml.dist` file. Fill in required fields:
```bash
cd syrup
cp app/config/parameters.yml.dist app/config/parameters.yml
```

- Install composer dependencies:
    - download and install composer as described [here](https://getcomposer.org/download/)
    - install dependencies
```bash
php composer.phar install
```

- Run tests
    - create `test.sh` from `test.sh.template`
```bash
cp test.sh.template test.sh
```
    - set values to environment variables needed
    - run tests
```bash
chmod a+x test.sh
./test.sh
```

Docker-ish Development
----------------------------------
*Note: non-persistent*

- Clone from GitHub
```bash
git clone https://github.com/keboola/syrup.git
```

- Install dependencies
```bash
php composer.phar install
```

- Create `test.sh` from `test.sh.template`
- Create AWS resources from [aws-services.json](./aws-services.json) and fill `SYRUP_AWS_KEY`, `SYRUP_AWS_SECRET`, `SYRUP_AWS_REGION`, `SYRUP_S3_BUCKET`, `SYRUP_SQS_URL` in `tests.sh`
- Run elasticsearch and mysql
```bash
docker-compose up elastic mysql
```
- Run tests
```bash
chmod a+x test.sh
./test.sh
```
