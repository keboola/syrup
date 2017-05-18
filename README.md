Syrup
========================

[![Build Status](https://travis-ci.org/keboola/syrup.svg?branch=master)](https://travis-ci.org/keboola/syrup)
[![Code Climate](https://codeclimate.com/github/keboola/syrup/badges/gpa.svg)](https://codeclimate.com/github/keboola/syrup)
[![Test Coverage](https://codeclimate.com/github/keboola/syrup/badges/coverage.svg)](https://codeclimate.com/github/keboola/syrup)


Syrup is a framework for rapid development of Keboola Connection components (i.e. extractors).
It is based on Symfony2 framework.

Development
----------------------------------

*Note: Elastic & MySQL non-persistent*

- Clone from GitHub
```bash
git clone https://github.com/keboola/syrup.git
```

- Set up Docker Compose
```bash
docker-compose build
```

- Create `.env` file with this content
```
SYRUP_APP_NAME=syrup-devel
DATABASE_HOST=mysql
DATABASE_USER=syrup
DATABASE_PASSWORD=syrup
DATABASE_NAME=syrup
DATABASE_PORT=3306
ELASTICSEARCH_HOST=elastic:9200
AWS_S3_BUCKET_LOGS_PATH=/debug-files
SAPI_URL=https://connection.keboola.com/

SAPI_TOKEN=

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_REGION=
AWS_S3_BUCKET=
AWS_SQS_DEFAULT_QUEUE=
AWS_SQS_TEST_QUEUE_NAME=

```

- Create AWS resources from [aws-services.json](./aws-services.json) and fill `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_REGION`, `AWS_S3_BUCKET`, `AWS_SQS_DEFAULT_QUEUE` and `AWS_SQS_TEST_QUEUE_NAME` in `.env`
- Insert a Storage API token into `SAPI_TOKEN`
- Run elasticsearch and mysql
```bash
docker-compose up elastic mysql
```
- Run tests
```bash
docker-compose run --rm tests
```
