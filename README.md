# dot GNE
## A simple, secure photo sharing system

Tom Royal / twitter.com/tomroyal / www.tomroyal.com

Dot GNE is a multi-user photo storage and sharing system, designed to replicate the key functions of flickr.com from back in the day. My aim was to have a small, private system for sharing pictures of our Small person, where I have complete control over the source images, the data, and who can see what.

It's built to run on Heroku using PHP (and Composer) and Postgres. Images are stored in Amazon S3, and processed via imgix.com.

The following key features are in place:

* Multiple users supported
* Paginated image list view
* Single image view
* Metadata: Title and Description
* EXIF dates read and stored (via Imgix, optional)
* Secured thumbnail URLs (via Imgix, optional)
* Multiple levels of privacy, per image:
    * Public
    * Visible to friends, family and the user
    * Visible to family and the user
    * Private to the user only
* Asynchronous upload to S3
* User can create account invitations via keyed URLs
* Can function to a basic level without Imgix, using createPresignedRequest S3 URLs

While the following are all // TODO:

* Image delete
* Multiple / batch upload and edit
* Sets / albums, possibly with slideshow
* Limited-use sharing links via keyed URLs, to images and albums

## Example

My personal dot GNE is at: https://photos.tomroyal.com

## Installation

You can now install dot GNE to Heroku using this URL:

https://dashboard.heroku.com/new?template=https://github.com/tomroyal/dotgne/master

Set your environment variables, then click Deploy App. Once deployed, click View to visit the setup script at /postinstall.php

Note that you will need to set your AWS environment variables - AWS_BUCKET, AWS_REGION, AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY to upload and view images. Your bucket will need a suitable CORS - see https://packagist.org/packages/eddturtle/direct-upload for examples.

If you configure an Imgix source, then add IMGIXSOURCE and IMGIXSIGN environment variables you will get much better performance (image thumbnails, EXIF data, correct image rotation, etc). Leaving IMGIXSOURCE as blank, or "tbc", indicates that Imgix should not be used.

I will add a full setup guide soon.

## License

Dot GNE is published under the GNU Affero license: https://www.gnu.org/licenses/agpl-3.0.en.html
