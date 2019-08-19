# ISBN Arama

###
##### Bu API get parametresi ile gönderilen isbn numarasının bilgilerini getirir.
Örneğin: https://api.orhanaydogdu.com.tr/isbn/index.php?isbn=9786052983690

Dönen cevap:
```json
{
	"status": true,
	"time": 1566255788,
	"desc": "Veriler yüklendi, dikkat verilerin doğruluğunu asla kabul etmiyoruz. - from db",
	"result": {
		"title": "Mustafa Kemal",
		"author": "Yılmaz Özdil",
		"publisher": "Kırmızı Kedi Yayınevi",
		"isbn": 9786052983690
	}
}
```

Örnekte verdiğim link üzerinden sınırsız şekilde kullanabilir geliştirmemi bu repo dan kontrol edebilirsiniz.

Eğer sonuç bulunamazsa veya herhangi bir hata söz konusu olduğunda http status olarak 404 dönmekte ve dönen json da status false olmakta. Herşey başarılı ve sonuç yüklendi ise http status 200 dönmekte ve http status true olarak olmakta. API durum bilgilerini desc keyinden alabilirsiniz.
##### Swagger döküman linki:
https://api.orhanaydogdu.com.tr/index.php?doc=isbn.yaml
#

##### Not:
env.php dosyamızda şifrelerimiz ve veritabanı bağlantılarımız bulunduğu için git için geçersiz kıldım.
