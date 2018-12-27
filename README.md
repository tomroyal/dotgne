# dot GNE
## A simple, secure photo sharing system

Dot GNE is a multi-user photo storage and sharing system, designed to replicate the key functions of flickr.com from back in the day. My aim was to have a small, private system for sharing pictures of our Small person, where I have complete control over the source images, the data, and who can see what.

It's built to run on Heroku using PHP (and Composer) and Postgres. Images are stored in Amazon S3, and processed via imgix.com.

The following key features are in place:

* Multiple users supported
* Paginated image list view
* Single image view
* Metadata: Title and Description
* EXIF dates read and stored
* Multiple levels of privacy, per image:
 * Public
 * Visible to friends, family and the user
 * Visible to family and the user
 * Private to the user only
* Asynchronous upload to S3
* Secured thumbnail URLs via Imgix
* User can create account invitations via keyed URLs

While the following are all // TODO:

* Image delete
* Multiple / batch upload and edit
* Sets / albums, possibly with slideshow
* Limited-use sharing links via keyed URLs, to images and albums
* Non-imgix option enabled when no imgix env variable is supplied

Dot GNE is published under the GNU Affero license: https://www.gnu.org/licenses/agpl-3.0.en.html
