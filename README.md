# WordPress CI-CD with coding standard

1. WordPress deployment: 

https://github.com/appleboy/ssh-action

https://stackoverflow.com/questions/61447350/automatically-pull-from-remote-using-github-actions

```
on:
 push:
   branches:
     - main
name: 🚀 Deploy website on push
jobs:
 release:
    runs-on: ubuntu-latest
    steps:
    - name: executing remote ssh commands using password
      uses: appleboy/ssh-action@master
      with:
        host: ${{ secrets.FTP_HOST }}
        username: ${{ secrets.FTP_USERNAME }}
        password: ${{ secrets.FTP_PASSWORD }}
        port: ${{ secrets.PORT }}
        script: |
          cd /var/www/html
          ${{ secrets.SCRIPT }}
```

2. WordPress Coding Standard:

https://github.com/rtCamp/action-phpcs-code-review

```
name: "CI"
on: pull_request
jobs:
  runPHPCSInspection:
    name: Run PHPCS inspection
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
      with:
        ref: ${{ github.event.pull_request.head.sha }}
    - name: Run PHPCS inspection
      uses: rtCamp/action-phpcs-code-review@v2
      env:
        GH_BOT_TOKEN: ${{ secrets.GH_BOT_TOKEN }}
        SKIP_FOLDERS: "foo,node_modules,vendor,tests,.github,wp-includes,wp-admin,wp-includes,wp-content/plugins,wp-content/uploads,wp-content/themes/twentytwenty,wp-content/themes/twentytwentyone,wp-content/themes/twentytwentytwo"
      with:
        args: "WordPress,WordPress-Core,WordPress-Docs"
```
