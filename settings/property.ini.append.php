<?php /*

[ContentStreamFilename]
name=ContentStreamFilename
id=ContentStreamFilename
package=TODO
displayName=Content Stream Filename
description=The content stream filename
propertyType=string
cardinality=single
updatability=readwrite
inherited=true
required=false
queryable=true
orderable=false
openChoice=false

[LastModificationDate]
name=LastModificationDate
id=LastModificationDate
package=TODO
displayName=Last Modified Date
description=The date this object was last modified
propertyType=datetime
cardinality=single
updatability=readonly
inherited=true
required=true
queryable=true
orderable=true
openChoice=false

[ContentStreamLength]
name=ContentStreamLength
id=ContentStreamLength
package=TODO
displayName=Content Stream Length
description=The length of the content stream
propertyType=integer
cardinality=single
updatability=readonly
inherited=true
required=false
queryable=true
orderable=true
openChoice=false

[LastModifiedBy]
name=LastModifiedBy
id=LastModifiedBy
package=TODO
displayName=Last Modified By
description=The authority who last modified this object
propertyType=string
cardinality=single
updatability=readonly
inherited=true
required=true
queryable=true
orderable=true
openChoice=false

[ContentStreamMimeType]
name=ContentStreamMimeType
id=ContentStreamMimeType
package=TODO
displayName=Content Stream MIME Type
description=The content stream MIME type
propertyType=string
cardinality=single
updatability=readonly
inherited=true
required=false
queryable=true
orderable=true
openChoice=false

[Uri]
name=Uri
id=Uri
package=TODO
displayName=URI
description=URI
propertyType=uri
cardinality=single
updatability=readonly
inherited=true
required=false
queryable=false
orderable=false
openChoice=false

[ContentStreamAllowed]
name=ContentStreamAllowed
id=ContentStreamAllowed
package=TODO
displayName=Content Stream Allowed
description=Is a content stream allowed?
propertyType=string
cardinality=single
updatability=readonly
inherited=true
required=true
queryable=false
orderable=false
choiceString["required"]=required
choiceString["allowed"]=allowed
choiceString["notallowed"]=notallowed
openChoice=false

[ObjectTypeId]
name=ObjectTypeId
id=ObjectTypeId
package=TODO
displayName=Object Type Id
description=The object type id
propertyType=id
cardinality=single
updatability=readonly
inherited=true
required=true
queryable=true
orderable=true
openChoice=false

[IsLatestVersion]
type=BooleanDefinition
name=IsLatestVersion
id=IsLatestVersion
package=TODO
displayName=Is Latest Version
description=Is this the latest version of the document?
propertyType=boolean
cardinality=single
updatability=readonly
inherited=true
required=true
queryable=false
orderable=false
openChoice=false

[IsVersionSeriesCheckedOut]
type=BooleanDefinition
name=IsVersionSeriesCheckedOut
id=IsVersionSeriesCheckedOut
package=TODO
displayName=Is Version Series Checked Out
description=Is the version series checked out?
propertyType=boolean
cardinality=single
updatability=readonly
inherited=true
required=true
queryable=false
orderable=false
openChoice=false

[CreatedBy]
type=StringDefinition
name=CreatedBy
id=CreatedBy
package=TODO
displayName=Created by
description=The authority who created this object
propertyType=string
cardinality=single
updatability=readonly
inherited=true
required=true
queryable=true
orderable=true
openChoice=false

[VersionSeriesCheckedOutBy]
name=VersionSeriesCheckedOutBy
id=VersionSeriesCheckedOutBy
package=TODO
displayName=Version Series Checked Out By
description=The authority who checked out this document version series
propertyType=string
cardinality=single
updatability=readonly
inherited=true
required=false
queryable=false
orderable=false
openChoice=false

[ContentStreamUri]
name=ContentStreamUri
id=ContentStreamUri
package=TODO
displayName=Content Stream URI
description=The content stream URI
propertyType=uri
cardinality=single
updatability=readonly
inherited=true
required=false
queryable=false
orderable=false
openChoice=false

[VersionSeriesId]
name=VersionSeriesId
id=VersionSeriesId
package=TODO
displayName=Version series id
description=The version series id
propertyType=id
cardinality=single
updatability=readonly
inherited=true
required=true
queryable=true
orderable=true
openChoice=false

[Name]
name=Name
id=Name
package=TODO
displayName=Name
description=Name
propertyType=string
cardinality=single
updatability=readwrite
inherited=true
required=true
queryable=true
orderable=true
openChoice=false

[CheckinComment]
name=CheckinComment
id=CheckinComment
package=TODO
displayName=Checkin Comment
description=The checkin comment
propertyType=string
cardinality=single
updatability=readonly
inherited=true
required=false
queryable=false
orderable=false
openChoice=false

[IsImmutable]
name=IsImmutable
id=IsImmutable
package=TODO
displayName=Is Immutable
description=Is the document immutable?
propertyType=boolean
cardinality=single
updatability=readonly
inherited=true
required=false
queryable=false
orderable=false
openChoice=false

[IsLatestMajorVersion]
name=IsLatestMajorVersion
id=IsLatestMajorVersion
package=TODO
displayName=Is Latest Major Version
description=Is this the latest major version of the document?
propertyType=boolean
cardinality=single
updatability=readonly
inherited=true
required=false
queryable=false
orderable=false
openChoice=false

[IsMajorVersion]
name=IsMajorVersion
id=IsMajorVersion
package=TODO
displayName=Is Major Version
description=Is this a major version of the document?
propertyType=boolean
cardinality=single
updatability=readonly
inherited=true
required=false
queryable=false
orderable=false
openChoice=false

[VersionLabel]
name=VersionLabel
id=VersionLabel
package=TODO
displayName=Version Label
description=The version label
propertyType=string
cardinality=single
updatability=readonly
inherited=true
required=true
queryable=true
orderable=false
openChoice=false

[VersionSeriesCheckedOutId]
name=VersionSeriesCheckedOutId
id=VersionSeriesCheckedOutId
package=TODO
displayName=Version Series Checked Out Id
description=The checked out version series id
propertyType=id
cardinality=single
updatability=readonly
inherited=true
required=false
queryable=false
orderable=false
openChoice=false

[ChangeToken]
name=ChangeToken
id=ChangeToken
package=TODO
displayName=Change token
description=Change Token
propertyType=string
cardinality=single
updatability=readonly
inherited=true
required=true
queryable=false
orderable=false
openChoice=false

[ObjectId]
name=ObjectId
id=ObjectId
package=TODO
displayName=Object Id
description=The unique object id (a node ref)
propertyType=id
cardinality=single
updatability=readonly
inherited=true
required=true
queryable=true
orderable=true
openChoice=false

[CreationDate]
name=CreationDate
id=CreationDate
package=TODO
displayName=Creation Date
description=The object creation date
propertyType=datetime
cardinality=single
updatability=readonly
inherited=true
required=true
queryable=true
orderable=true
openChoice=false


*/ ?]