import re
with open('app/Http/Controllers/MonitoringScheduleController.php', 'r', encoding='utf-8') as f:
    c = f.read()

# Fix regex for finding meet links to be much broader
c = c.replace(
    'preg_match_all(\'/https:\/\/(?:meet|hangouts)\.google\.com\/[a-z0-9\-]+/i\'', # just in case
    'preg_match_all(\'/https?:\/\/(?:meet\.google\.com|meet\.google\.com\/lookup)\/[^\s"\'<>\?]+/i\''
)
c = c.replace(
    'preg_match_all(\'/https:\/\/meet\.google\.com\/[a-z0-9\-]+/i\'',
    'preg_match_all(\'/https?:\/\/meet\.google\.com\/[^\s"\'<>\?]+/i\''
)
c = c.replace(
    'preg_match(\'/https:\/\/meet\.google\.com\/[a-z0-9\-]+/i\'',
    'preg_match(\'/https?:\/\/meet\.google\.com\/[^\s"\'<>\?]+/i\''
)
c = c.replace(
    'preg_match_all(\'/href="([^"]*mod\/url\/view\.php\?id=\d+)"/i\'',
    'preg_match_all(\'/href="([^"]*mod\/(?:url|page)\/view\.php\?id=\d+)"/i\'' # also scrape pages!
)

# Also search inside URL decoded HTML just in case Moodle click tracker urlencodes it
c = c.replace(
    'if (preg_match_all(\'/https?:\/\/meet\.google\.com\/[^\s"\'<>\?]+/i\', $htmlCourse, $mLinks)) {',
    '$decodedHtmlCourse = urldecode($htmlCourse);\n                                  if (preg_match_all(\'/https?:\/\/meet\.google\.com\/[^\s"\'<>\?]+/i\', $decodedHtmlCourse, $mLinks)) {'
)


with open('app/Http/Controllers/MonitoringScheduleController.php', 'w', encoding='utf-8') as f:
    f.write(c)
print('Done')
