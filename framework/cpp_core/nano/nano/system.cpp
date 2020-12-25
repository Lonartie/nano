#include "system.h"
#include "registry.h"
#include <QStringList>
#include <QJsonObject>
#include <QJsonDocument>
#include <QList>
#include <QVector>
#include <iostream>
using namespace nano;

void print(const QJsonObject& obj);
void printDefinitions();

int nano::run(const int argc, char** argv)
{
	try
	{
		const auto args = QStringList(QList<QString>::fromVector(QVector<QString>::fromStdVector(std::vector<QString>(argv + 1, argv + argc))));

		if (args.size() < 2)
		{
			printDefinitions();
			return 0;
		}

		const auto service = args.at(0);
		const auto method = args.at(1);
		const auto methodArgs = QStringList(QList<QString>::fromVector(QVector<QString>::fromStdVector(std::vector<QString>(args.begin() + 2, args.end()))));

		if (registry::creators.find(service) == registry::creators.end()) throw std::exception(QString("the binary does not contain a service called '%1'").arg(service).toStdString().c_str());
		print((*registry::creators[service]())[method](methodArgs));
	}
	catch (const std::exception& ex)
	{
		print({{ "success", "false" }, { "message", ex.what() }});
	}
	catch (...)
	{
		print({{ "success", "false" }, { "message", "unknown error occured" }});
	}

	return 0;
}

void print(const QJsonObject& obj)
{
	std::cout
		<< QJsonDocument(obj).toJson(QJsonDocument::Compact).toStdString()
		<< std::endl;
}

void printDefinitions()
{
	QJsonObject services;
	for (auto& [name, creator] : registry::creators)
	{
		services.insert(name, creator()->definition());
	}

	const QJsonObject result{
		{ "success", true },
		{ "services", services}
	};

	print(result);
}
