<p align="center">
    <img src="public/strava.png"
         alt="Strava">
</p>

# Strava activities template

This repository contains the template code to generate your own Strava statistics pages.
Just follow the steps below. If you experience any issues with any of the steps,
feel free to [open an issue](https://github.com/robiningelbrecht/strava-activities-template/issues/new). I'll be glad to help you out 💅.

## Examples

* Markdown version: https://github.com/robiningelbrecht/strava-activities
* HTML version: https://strava-statistics.robiningelbrecht.be/

## What you'll need

* A Strava API key
* A GitHub account

## Installation

* Make sure your logged in with your GitHub account
* Start off by showing some ❤️ and give this repo a star
* [Create a new repository](https://github.com/new?template_name=strava-activities-template&template_owner=robiningelbrecht) using this template
* Navigate to your newly created repository `Actions secrets and variables` page (https://github.com/[YOUR-GITHUB-USERNAME]/[REPOSITORY-NAME]/settings/secrets/actions)
  Keep this page open, you will need to add several secrets here
* Next, navigate to your [Strava API settings page](https://www.strava.com/settings/api).
  Copy the `client ID` and `client secret`
  ![Strava API keys](files/install/strava-api-keys.png)
* Create two new repository secrets
  ![Repo secrets](files/install/repository-secrets.png)
    * __name__: STRAVA_CLIENT_ID, __value__: `client ID` copied from Strava API settings page
    * __name__: STRAVA_CLIENT_SECRET, __value__: `client secret` copied from Strava API settings page
* Now you need to obtain a `Strava API refresh token`. This might be the hardest step.
    * Navigate to https://developers.strava.com/docs/getting-started/#d-how-to-authenticate
      and scroll down to "_For demonstration purposes only, here is how to reproduce the graph above with cURL:_"
    * Follow the 11 steps explained there
    * Make sure you set the `scope` in step 2 to `activity:read_all` to make sure your refresh token has access to all activities
      ![Refresh token](files/install/strava-refresh-token.png)
    * Create a repository secret with the refresh token you obtained: __name__: STRAVA_REFRESH_TOKEN, __value__: The `refresh token` you just obtained
* You should end up with these repository secrets:
  ![Repository secrets](files/install/secrets-example.png)
* After this you need to make sure the automated workflow is able to push changes to your repo.
    * Navigate to https://github.com/[YOUR-GITHUB-USERNAME]/[REPOSITORY-NAME]/settings/actions
    * Scroll down to `Workflow permissions` and make sure `Read and write permissions` is checked
      ![Workflow permissions](files/install/workflow-permissions.png)
* The last thing you need to do configure the automated workflow.
  * Navigate to https://github.com/[YOUR-GITHUB-USERNAME]/[REPOSITORY-NAME]/edit/master/.github/workflows
  * Create a new file called `update-strava-activities.yml` and copy over the contents from `update-strava-activities.ymy.dist`
  * Edit the `update-strava-activities.yml` file:
      * Navigate to https://github.com/[YOUR-GITHUB-USERNAME]/[REPOSITORY-NAME]/edit/master/.github/workflows/update-strava-activities.yml
      * Scroll down to
      ```yml
      name: Commit and push changes
      run: |
        git config --global user.name 'YOUR_GITHUB_USERNAME'
        git config --global user.email 'YOUR_GITHUB_USERNAME@users.noreply.github.com'
        git add .
        git status
        git diff --staged --quiet || git commit -m"Updated strava activities"
        git push
      ```

      * Replace `YOUR_GITHUB_USERNAME` with your own username
      * Click `commit changes` at the top right-hand corner

## Some things to consider

* Only (virtual) bike rides are imported, other sports are not relevant for these stats
* Because of technical (Strava) limitations, not all Strava challenges
  can be imported. Only the visible ones on your public profile can be imported
* Strava statistics will be re-calculated once a day. If you want to
  re-calculate these manually, navigate to https://github.com/[YOUR-GITHUB-USERNAME]/[REPOSITORY-NAME]/actions/workflows/update-strava-activities.yml
  and click `Run workflow` at the right-hand side
* Running the import for the first time can take a while, depending on how many activities you have on Strava.
  Strava's API has a rate limit of 100 request per 15 minutes and a 1000 requests per day. We have to make sure
  this limit is not exceeded. See https://developers.strava.com/docs/rate-limits/. If you have more than 500 activities,
  you might run into the daily rate limit. If you do so, the app will import the remaining activities the next day(s).
## 💡Feature request?

For any feedback, help or feature requests, please [open a new issue](https://github.com/robiningelbrecht/strava-activities-template/issues/new)

